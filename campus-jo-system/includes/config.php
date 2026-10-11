<?php
// SETTINGS used across the system

const ALLOWED_DOMAIN   = 'rtu.edu.ph';                                   // only this email domain can register / sign in
const GOOGLE_CLIENT_ID = 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com'; // keep your own if you already pasted it

// Gemini AI (get a free key at aistudio.google.com). NEVER put this key in HTML/JavaScript.
const GEMINI_API_KEY = 'YOUR_GEMINI_API_KEY';
const GEMINI_MODEL   = 'gemini-2.5-flash';

const REQUIRE_LOGIN = true;   // pages need a logged-in user (set false only to preview the UI)
const OTP_LENGTH    = 6;      // boxes on the 2FA page

const CATEGORIES = ['Electrical', 'Plumbing', 'Carpentry', 'Cleanliness', 'IT Equipment', 'Other'];
const PRIORITIES = ['Low', 'Medium', 'High'];
const STATUSES   = ['Pending', 'Assigned', 'In-Progress', 'Complete'];
const TEAMS      = ['Electrical Team', 'Plumbing Team', 'Carpentry Team', 'Cleaning Team', 'IT Support', 'General Maintenance'];
