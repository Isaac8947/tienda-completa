<?php
/**
 * SECURITY HEADERS - Apply to all pages
 * Include this at the top of every page
 */

// Prevent clickjacking
header("X-Frame-Options: DENY");

// Prevent MIME type sniffing
header("X-Content-Type-Options: nosniff");

// Enable XSS protection
header("X-XSS-Protection: 1; mode=block");

// Referrer policy
header("Referrer-Policy: strict-origin-when-cross-origin");

// Content Security Policy
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://cdn.tailwindcss.com https://unpkg.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com https://unpkg.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: https:; connect-src 'self';");

// Strict Transport Security (HTTPS)
if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on") {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
}

// Feature Policy
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

// Remove server information
header_remove("X-Powered-By");
header_remove("Server");

?>