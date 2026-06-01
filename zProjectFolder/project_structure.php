BUSureLetsChat/
│
├── index.php
├── .htaccess
├── .env
├── .env.example
│
├── auth/
│   │
│   ├── login.php
│   ├── register.php
│   ├── forgot_password.php
│   ├── reset_password.php
│   ├── logout.php
│   │
│   └── content/
│       ├── bisure_login_content.php
│       ├── bisure_register_content.php
│       ├── bisure_forgot_password_content.php
│       ├── bisure_reset_password_content.php
│       └── bisure_logout_content.php
│
├── chat/
│   │
│   ├── chats.php
│   ├── messages.php
│   ├── contacts.php
│   ├── archived.php
│   │
│   └── content/
│       ├── bisure_chat_content.php
│       ├── bisure_message_content.php
│       ├── bisure_contact_content.php
│       └── bisure_archive_content.php
│
├── groups/
│   │
│   ├── groups.php
│   ├── create_group.php
│   ├── group_info.php
│   │
│   └── content/
│       ├── bisure_group_content.php
│       ├── bisure_create_group_content.php
│       └── bisure_group_info_content.php
│
├── calls/
│   │
│   ├── voice_call.php
│   ├── video_call.php
│   ├── call_history.php
│   │
│   └── content/
│       ├── bisure_voice_call_content.php
│       ├── bisure_video_call_content.php
│       └── bisure_call_history_content.php
│
├── settings/
│   │
│   ├── profile.php
│   ├── account_settings.php
│   │
│   └── content/
│       ├── bisure_profile_content.php
│       └── bisure_account_settings_content.php
│
├── config/
│   │
│   ├── env.php
│   ├── constants.php
│   ├── db.php
│   ├── session.php
│   ├── security.php
│   └── mail.php
│
├── includes/
│   │
│   ├── header.php
│   ├── navbar.php
│   ├── sidebar.php
│   ├── footer.php
│   ├── auth_check.php
│   └── functions.php
│
├── services/
│   │
│   ├── ChatService.php
│   ├── CallService.php
│   ├── EmailService.php
│   └── UploadService.php
│
├── mail/
│   │
│   ├── Mailer.php
│   │
│   └── templates/
│       ├── contact_us.php
│       ├── forgot_password.php
│       └── verification.php
│
├── assets/
│   │
│   ├── css/
│   ├── js/
│   ├── images/
│   ├── icons/
│   └── sounds/
│
├── uploads/
│   │
│   ├── images/
│   ├── videos/
│   ├── files/
│   └── profiles/
│
├── cache/
│
├── database/
│   │
│   └── schema.sql
│
├── logs/
│
└── errors.log