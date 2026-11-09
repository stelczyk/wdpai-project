<?php

declare(strict_types=1);

return [
    // Shared: header, footer, theme, language
    'footer.brand' => 'Dystans Virtual Expeditions',
    'footer.copyright' => 'All rights reserved © {year}',
    'footer.terms' => 'Expedition terms',
    'footer.privacy' => 'Privacy policy',
    'footer.support' => 'Technical support',
    'footer.status' => 'Satellite status: Connected',
    'nav.label' => 'Main menu',
    'nav.expeditions' => 'Expeditions',
    'nav.add_activity' => 'Add activity',
    'nav.login' => 'Log in',
    'theme.toggle' => 'Toggle theme',
    'theme.to_light' => 'Switch to light theme',
    'theme.to_dark' => 'Switch to dark theme',
    'lang.label' => 'Language',
    'lang.pl' => 'Polski',
    'lang.en' => 'English',

    // Home
    'home.title' => 'Home',
    'home.tagline' => 'Virtual expeditions with friends: every workout adds kilometres to a shared route.',
    'home.cta' => 'Browse expeditions',

    // Expeditions
    'expeditions.title' => 'Expeditions',
    'expedition.title' => 'Expedition dashboard',
    'expedition.heading' => 'Expedition: {name}',
    'expedition.progress' => 'Progress',
    'expedition.nav_label' => 'Expedition #{id}',
    'expedition.ranking' => 'Leaderboard',
    'expedition.captain_panel' => 'Captain panel',

    // Leaderboard
    'ranking.title' => 'Expedition leaderboard',
    'ranking.heading' => 'Leaderboard – expedition #{id}',
    'ranking.position' => 'Rank',
    'ranking.participant' => 'Participant',
    'ranking.km' => 'Km',
    'ranking.back' => 'Back to expedition',

    // Captain panel
    'captain.title' => 'Captain panel',
    'captain.heading' => 'Captain panel – expedition #{id}',
    'captain.pending' => 'Activities awaiting approval',

    // Add activity
    'activity.title' => 'Add activity',
    'activity.type' => 'Type',
    'activity.distance' => 'Distance (km)',
    'activity.date' => 'Date',
    'activity.photo' => 'Photo',
    'activity.submit' => 'Save',

    // Sign in and sign up – shared
    'auth.email' => 'Email address',
    'auth.email_placeholder' => 'john.smith@dystans.app',
    'auth.password' => 'Password',
    'auth.show_password' => 'Show password',
    'auth.hide_password' => 'Hide password',
    'auth.close_alert' => 'Dismiss notification',

    // Sign in
    'login.title' => 'Sign in',
    'login.heading' => 'Sign in to your expedition',
    'login.lead' => 'Enter your telemetry credentials to continue the shared route.',
    'login.error_title' => 'Expedition authorization failed',
    'login.email_hint' => 'Linked to your expedition account',
    'login.forgot' => 'Forgot your password?',
    'login.remember' => 'Remember me on this device',
    'login.submit' => 'Sign in',
    'login.or' => 'Or',
    'login.google' => 'Continue with Google',
    'login.strava' => 'Continue with Strava',
    'login.no_account' => 'Don\'t have an expedition profile yet?',
    'login.register_link' => 'Create a new team account',

    // Sign up
    'register.title' => 'Sign up',
    'register.heading' => 'Join the expedition',
    'register.lead' => 'Create a participant profile and start turning workouts into route kilometres.',
    'register.error_title' => 'Could not create the account',
    'register.name' => 'Name or nickname',
    'register.name_placeholder' => 'John Smith',
    'register.name_hint' => 'This is how others will see you on the leaderboard',
    'register.password_hint' => 'At least 8 characters',
    'register.password_confirm' => 'Repeat password',
    'register.terms' => 'I accept the expedition terms',
    'register.submit' => 'Create account',
    'register.has_account' => 'Already have an expedition profile?',
    'register.login_link' => 'Sign in',

    // Error pages
    'error.back' => 'Back to the home page',
    'error.400.title' => 'Bad request',
    'error.400.message' => 'The address contains invalid data, e.g. a malformed identifier.',
    'error.401.title' => 'Sign-in required',
    'error.401.message' => 'Sign in to see this page.',
    'error.403.title' => 'Access denied',
    'error.403.message' => 'You do not have permission to view this page.',
    'error.404.title' => 'Page not found',
    'error.404.message' => 'This page does not exist or has been removed.',
    'error.500.title' => 'Server error',
    'error.500.message' => 'Something went wrong on our side. Please try again in a moment.',
];
