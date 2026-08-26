<?php

// UI text for the login and 2FA pages. Named auth_pages.php (not auth.php) so it
// never collides with Laravel's own framework-published lang/en/auth.php later.
return [
    'login' => [
        'official_portal'  => 'Official Portal',
        'system_title'     => 'Integrated<br>Barangay<br>Management<br>System',
        'tagline'          => 'A centralized platform for managing resident records, complaint cases, document issuance, and community announcements.',
        'feature_system'   => 'System',
        'feature_resident_records' => 'Resident Records',
        'feature_secure'   => 'Secure',
        'feature_access_control' => 'Access Control',
        'footer'           => 'Motiong, Samar — © :year All rights reserved.',
        'welcome_back'     => 'Welcome back',
        'subtitle'         => 'Sign in to access the management system.',
        'email_label'      => 'Email',
        'email_placeholder' => 'Enter your email',
        'password_label'   => 'Password',
        'password_placeholder' => 'Enter your password',
        'forgot_password'  => 'Forgot password?',
        'sign_in'          => 'Sign In',
        'resident_question' => 'Are you a resident?',
        'create_account'   => 'Create an account',
        'verify_title'     => 'Verify a Barangay Document',
        'verify_desc'      => 'Check if an issued document is authentic using its OR or tracking number.',
        'verify_button'    => 'Verify',
        'authorized_only'  => 'Authorized personnel only — Brgy. Caranas, Motiong, Samar',
    ],
    'two_factor' => [
        'title'            => 'Verify Your Login',
        'subtitle'         => 'We sent a 6-digit code to :email. Enter it below to finish signing in.',
        'code_label'       => 'Verification Code',
        'verify_button'    => 'Verify &amp; Sign In',
        'resend_prompt'    => "Didn't get a code?",
        'resend_button'    => 'Resend',
        'not_you'          => 'Not you? Back to Sign In',
    ],
];
