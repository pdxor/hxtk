using UnityEngine;
using UnityEngine.XR;
using UnityEngine.XR.Interaction.Toolkit;

public class VRPlayerController : MonoBehaviour
{
    [Header("Movement Settings")]
    [SerializeField] private float moveSpeed = 1.0f;
    [SerializeField] private float turnSpeed = 45.0f;
    
    [Header("References")]
    [SerializeField] private XRNode leftHandNode = XRNode.LeftHand;
    [SerializeField] private XRNode rightHandNode = XRNode.RightHand;
    
    private CharacterController characterController;
    private XRRig xrRig;
    
    private void Start()
    {
        characterController = GetComponent<CharacterController>();
        xrRig = GetComponent<XRRig>();
        
        if (characterController == null || xrRig == null)
        {
            Debug.LogError("Missing required components on VR Player!");
            enabled = false;
            return;
        }
    }
    
    private void Update()
    {
        HandleMovement();
        HandleRotation();
    }
    
    private void HandleMovement()
    {
        // Get input from left controller
        InputDevice leftHandDevice = InputDevices.GetDeviceAtXRNode(leftHandNode);
        Vector2 movement = Vector2.zero;
        
        if (leftHandDevice.TryGetFeatureValue(CommonUsages.primary2DAxis, out movement))
        {
            // Convert input to world space movement
            Quaternion headYaw = Quaternion.Euler(0, xrRig.cameraGameObject.transform.eulerAngles.y, 0);
            Vector3 direction = headYaw * new Vector3(movement.x, 0, movement.y);
            
            characterController.Move(direction * moveSpeed * Time.deltaTime);
        }
    }
    
    private void HandleRotation()
    {
        // Get input from right controller
        InputDevice rightHandDevice = InputDevices.GetDeviceAtXRNode(rightHandNode);
        Vector2 rotation = Vector2.zero;
        
        if (rightHandDevice.TryGetFeatureValue(CommonUsages.primary2DAxis, out rotation))
        {
            // Snap turn when pushing stick left/right
            if (Mathf.Abs(rotation.x) > 0.5f)
            {
                float turnAmount = turnSpeed * Mathf.Sign(rotation.x);
                transform.RotateAround(xrRig.cameraGameObject.transform.position, Vector3.up, turnAmount);
            }
        }
    }
}

public class VRInteractionManager : MonoBehaviour
{
    [Header("Interaction Settings")]
    [SerializeField] private float grabThreshold = 0.5f;
    [SerializeField] private LayerMask interactableLayer;
    
    private XRController leftController;
    private XRController rightController;
    
    private void Start()
    {
        leftController = GetComponentInChildren<XRController>(true);
        rightController = GetComponentInChildren<XRController>(true);
        
        if (leftController == null || rightController == null)
        {
            Debug.LogError("Missing XR controllers!");
            enabled = false;
            return;
        }
        
        SetupInteractionEvents();
    }
    
    private void SetupInteractionEvents()
    {
        // Setup grip button events
        leftController.selectAction.action.performed += ctx => TryGrab(leftController);
        leftController.selectAction.action.canceled += ctx => Release(leftController);
        
        rightController.selectAction.action.performed += ctx => TryGrab(rightController);
        rightController.selectAction.action.canceled += ctx => Release(rightController);
    }
    
    private void TryGrab(XRController controller)
    {
        RaycastHit hit;
        Ray ray = new Ray(controller.transform.position, controller.transform.forward);
        
        if (Physics.Raycast(ray, out hit, 1.0f, interactableLayer))
        {
            IVRInteractable interactable = hit.collider.GetComponent<IVRInteractable>();
            if (interactable != null)
            {
                interactable.OnGrab(controller);
            }
        }
    }
    
    private void Release(XRController controller)
    {
        // Implementation for releasing grabbed objects
    }
}

public interface IVRInteractable
{
    void OnGrab(XRController controller);
    void OnRelease(XRController controller);
} 