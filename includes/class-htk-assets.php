<?php

// Define the class
class HTK_Assets {
    // Constructor to initialize actions
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
    }

    // Method to enqueue public assets
    public function enqueue_public_assets() {
        // Add Google Fonts
        wp_enqueue_style(
            'htk-fonts',
            'https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600&family=Space+Mono:wght@400;700&display=swap',
            array(),
            '1.0.0' // Replace with HTK_VERSION if defined
        );

        // Add our custom styles
        wp_enqueue_style(
            'htk-single-hackathon',
            plugin_dir_url(__FILE__) . 'assets/css/public/single-hackathon.css',
            array(),
            '1.0.0' // Replace with HTK_VERSION if defined
        );
    }
}

// Initialize the class
new HTK_Assets();
