using UnityEngine;
using UnityEngine.XR;
using System.Collections.Generic;

public class HandTrackingManager : MonoBehaviour
{
    [Header("Hand Settings")]
    [SerializeField] private GameObject handPrefab;
    [SerializeField] private float fingerSmoothTime = 0.1f;
    
    private HandController leftHand;
    private HandController rightHand;
    private List<InputDevice> devices = new List<InputDevice>();

    private void Start()
    {
        InitializeHands();
        InputDevices.deviceConnected += OnDeviceConnected;
        InputDevices.deviceDisconnected += OnDeviceDisconnected;
    }

    private void InitializeHands()
    {
        // Create hand instances
        leftHand = CreateHand(XRNode.LeftHand);
        rightHand = CreateHand(XRNode.RightHand);
        
        // Get currently connected devices
        InputDevices.GetDevices(devices);
        foreach (var device in devices)
        {
            OnDeviceConnected(device);
        }
    }

    private HandController CreateHand(XRNode handNode)
    {
        GameObject handObject = Instantiate(handPrefab, transform);
        HandController controller = handObject.AddComponent<HandController>();
        controller.Initialize(handNode, fingerSmoothTime);
        return controller;
    }

    private void OnDeviceConnected(InputDevice device)
    {
        if ((device.characteristics & InputDeviceCharacteristics.HandTracking) != 0)
        {
            if ((device.characteristics & InputDeviceCharacteristics.Left) != 0)
            {
                leftHand.SetInputDevice(device);
            }
            else if ((device.characteristics & InputDeviceCharacteristics.Right) != 0)
            {
                rightHand.SetInputDevice(device);
            }
        }
    }

    private void OnDeviceDisconnected(InputDevice device)
    {
        if ((device.characteristics & InputDeviceCharacteristics.HandTracking) != 0)
        {
            if ((device.characteristics & InputDeviceCharacteristics.Left) != 0)
            {
                leftHand.ClearInputDevice();
            }
            else if ((device.characteristics & InputDeviceCharacteristics.Right) != 0)
            {
                rightHand.ClearInputDevice();
            }
        }
    }
}

public class HandController : MonoBehaviour
{
    private XRNode handNode;
    private InputDevice? inputDevice;
    private Transform[] fingerJoints;
    private Vector3[] fingerVelocities;
    private float smoothTime;
    
    private static readonly HandFinger[] TrackingFingers = new[]
    {
        HandFinger.Thumb,
        HandFinger.Index,
        HandFinger.Middle,
        HandFinger.Ring,
        HandFinger.Pinky
    };

    public void Initialize(XRNode node, float smoothingTime)
    {
        handNode = node;
        smoothTime = smoothingTime;
        SetupFingerJoints();
    }

    private void SetupFingerJoints()
    {
        // Initialize arrays for joint tracking
        fingerJoints = new Transform[25]; // 5 fingers * 5 joints per finger
        fingerVelocities = new Vector3[25];
        
        // Find and store all finger joint transforms
        Transform handTransform = transform;
        int jointIndex = 0;
        
        foreach (HandFinger finger in TrackingFingers)
        {
            Transform fingerBase = handTransform.Find(finger.ToString());
            if (fingerBase != null)
            {
                Transform current = fingerBase;
                for (int joint = 0; joint < 5; joint++)
                {
                    fingerJoints[jointIndex] = current;
                    jointIndex++;
                    
                    if (current.childCount > 0)
                        current = current.GetChild(0);
                }
            }
        }
    }

    public void SetInputDevice(InputDevice device)
    {
        inputDevice = device;
    }

    public void ClearInputDevice()
    {
        inputDevice = null;
    }

    private void Update()
    {
        if (!inputDevice.HasValue)
            return;

        UpdateHandTracking();
        UpdateGestures();
    }

    private void UpdateHandTracking()
    {
        int jointIndex = 0;
        
        foreach (HandFinger finger in TrackingFingers)
        {
            for (int joint = 0; joint < 5; joint++)
            {
                if (inputDevice.Value.TryGetFeatureValue(
                    CommonUsages.handData,
                    out HandData handData))
                {
                    Bone bone = handData.GetBone((int)finger * 5 + joint);
                    Transform jointTransform = fingerJoints[jointIndex];
                    
                    if (jointTransform != null)
                    {
                        // Smoothly update joint position and rotation
                        jointTransform.position = Vector3.SmoothDamp(
                            jointTransform.position,
                            bone.TryGetPosition(out Vector3 position) ? position : jointTransform.position,
                            ref fingerVelocities[jointIndex],
                            smoothTime
                        );
                        
                        if (bone.TryGetRotation(out Quaternion rotation))
                        {
                            jointTransform.rotation = rotation;
                        }
                    }
                }
                jointIndex++;
            }
        }
    }

    private void UpdateGestures()
    {
        // Detect common gestures like pinch, grab, point
        if (inputDevice.Value.TryGetFeatureValue(
            CommonUsages.indexTouch,
            out float indexPinch))
        {
            // Pinch gesture detection
            if (indexPinch > 0.9f)
            {
                OnPinchGesture();
            }
        }
    }

    private void OnPinchGesture()
    {
        // Implement pinch gesture response
        RaycastHit hit;
        if (Physics.Raycast(transform.position, transform.forward, out hit))
        {
            IHandInteractable interactable = hit.collider.GetComponent<IHandInteractable>();
            interactable?.OnPinch(this);
        }
    }
}

public interface IHandInteractable
{
    void OnPinch(HandController hand);
    void OnRelease(HandController hand);
} 