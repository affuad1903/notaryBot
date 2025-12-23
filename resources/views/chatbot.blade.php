<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>NotaryBot</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- CSS -->
    <link rel="stylesheet" href="{{ asset('css/chatbot.css') }}">
</head>
<body>

<div class="chatbot-container">
    <div class="chat-header">
        🤖 NotaryBot
    </div>

    <!-- Form Registrasi -->
    <div class="registration-form" id="registrationForm">
        <div style="padding: 20px;">
            <h3 style="margin-top: 0; color: #1e3a8a;">Selamat Datang!</h3>
            <p style="color: #6b7280; margin-bottom: 20px;">Silakan isi data Anda untuk memulai chat</p>
            
            <form id="userForm" onsubmit="submitRegistration(event)">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; color: #374151; font-weight: 500;">Nama:</label>
                    <input type="text" id="userName" name="name" required 
                           style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 5px; font-size: 14px;"
                           placeholder="Masukkan nama Anda">
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; color: #374151; font-weight: 500;">Email:</label>
                    <input type="email" id="userEmail" name="email" required 
                           style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 5px; font-size: 14px;"
                           placeholder="contoh@email.com">
                </div>
                
                <button type="submit" 
                        style="width: 100%; padding: 12px; background: #2563eb; color: white; border: none; border-radius: 5px; font-size: 16px; font-weight: 500; cursor: pointer;">
                    Mulai Chat
                </button>
            </form>
        </div>
    </div>

    <!-- Chat Area -->
    <div class="chat-content" id="chatContent" style="display: none;">
        <div class="chat-body" id="chatBody">
            <!-- pesan bot & user -->
        </div>

        <div class="chat-footer">
            <input type="text" id="userInput" placeholder="Ketik pesan..." autocomplete="off">
            <button onclick="sendMessage()">Kirim</button>
        </div>
    </div>
</div>

<!-- JS -->
<script src="{{ asset('js/chatbot.js') }}"></script>
</body>
</html>
