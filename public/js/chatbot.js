const chatBody = document.getElementById('chatBody');
const userInput = document.getElementById('userInput');
const registrationForm = document.getElementById('registrationForm');
const chatContent = document.getElementById('chatContent');
const CHAT_HISTORY_KEY = 'notarybot_chat_history';
const CHAT_TIMESTAMP_KEY = 'notarybot_chat_timestamp';
const USER_REGISTERED_KEY = 'notarybot_user_registered';
const SESSION_TIMEOUT = 15 * 60 * 1000; // 15 menit
const FOLLOWUP_TIMEOUT = 1 * 60 * 1000; // 1 menit untuk follow-up

let inactivityTimer = null;
let followUpShown = false;

document.addEventListener('DOMContentLoaded', () => {
    checkUserRegistration();
});

// Cek apakah user sudah terdaftar
function checkUserRegistration() {
    fetch('/chatbot/check-user')
        .then(res => res.json())
        .then(data => {
            if (data.registered) {
                showChat();
                loadChatHistory();
                
                const history = getChatHistory();
                if (!history || history.length === 0) {
                    loadWelcome();
                } else {
                    startInactivityTimer();
                }
            } else {
                showRegistrationForm();
            }
        })
        .catch(err => {
            console.error('Error checking user:', err);
            showRegistrationForm();
        });
}

function showRegistrationForm() {
    registrationForm.style.display = 'block';
    chatContent.style.display = 'none';
}

function showChat() {
    registrationForm.style.display = 'none';
    chatContent.style.display = 'flex';
    chatContent.style.flexDirection = 'column';
    chatContent.style.flex = '1';
}

// Submit form registrasi
function submitRegistration(event) {
    event.preventDefault();
    
    const name = document.getElementById('userName').value;
    const email = document.getElementById('userEmail').value;
    
    fetch('/chatbot/register', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ name, email })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            localStorage.setItem(USER_REGISTERED_KEY, 'true');
            showChat();
            clearChatHistory();
            loadWelcome();
        }
    })
    .catch(err => {
        console.error('Registration error:', err);
        alert('Terjadi kesalahan saat registrasi. Silakan coba lagi.');
    });
}

// Timer untuk follow-up message
function startInactivityTimer() {
    clearTimeout(inactivityTimer);
    followUpShown = false;
    
    inactivityTimer = setTimeout(() => {
        if (!followUpShown) {
            showFollowUpMessage();
            followUpShown = true;
        }
    }, FOLLOWUP_TIMEOUT);
}

function resetInactivityTimer() {
    startInactivityTimer();
}

function showFollowUpMessage() {
    addMessageToDOM('Apakah ada pertanyaan lain?', 'bot');
    showReviewButtons();
}

function showReviewButtons() {
    const reviewContainer = document.createElement('div');
    reviewContainer.className = 'review-buttons';
    reviewContainer.innerHTML = `
        <div style="margin-top: 10px;">
            <p style="font-size: 13px; color: #6b7280; margin-bottom: 8px;">Bagaimana pengalaman Anda?</p>
            <div style="display: flex; gap: 10px;">
                <button onclick="submitReview('positive')" class="review-btn positive">
                    ��� Baik
                </button>
                <button onclick="submitReview('negative')" class="review-btn negative">
                    ��� Kurang Baik
                </button>
            </div>
        </div>
    `;
    chatBody.appendChild(reviewContainer);
    chatBody.scrollTop = chatBody.scrollHeight;
}

function submitReview(rating) {
    fetch('/chatbot/review', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ rating })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Hapus review buttons
            const reviewButtons = document.querySelector('.review-buttons');
            if (reviewButtons) {
                reviewButtons.remove();
            }
            
            addMessageToDOM('Terima kasih atas feedback Anda! ���', 'bot');
            clearTimeout(inactivityTimer);
        }
    })
    .catch(err => {
        console.error('Review error:', err);
    });
}

function getChatHistory() {
    const timestamp = localStorage.getItem(CHAT_TIMESTAMP_KEY);
    const now = Date.now();
    
    if (timestamp && (now - parseInt(timestamp)) > SESSION_TIMEOUT) {
        clearChatHistory();
        return [];
    }
    
    const history = localStorage.getItem(CHAT_HISTORY_KEY);
    return history ? JSON.parse(history) : [];
}

function saveChatHistory(message, sender, isChip = false) {
    const history = getChatHistory();
    history.push({ message, sender, isChip, timestamp: Date.now() });
    localStorage.setItem(CHAT_HISTORY_KEY, JSON.stringify(history));
    updateChatTimestamp();
}

function updateChatTimestamp() {
    localStorage.setItem(CHAT_TIMESTAMP_KEY, Date.now().toString());
}

function clearChatHistory() {
    localStorage.removeItem(CHAT_HISTORY_KEY);
    localStorage.removeItem(CHAT_TIMESTAMP_KEY);
    if (chatBody) {
        chatBody.innerHTML = '';
    }
}

function loadChatHistory() {
    const history = getChatHistory();
    
    if (history.length > 0) {
        history.forEach(item => {
            if (item.isChip) {
                const chipContainer = document.createElement('div');
                chipContainer.className = 'chips';
                
                item.message.forEach(option => {
                    const chip = document.createElement('div');
                    chip.className = 'chip';
                    chip.innerText = option.text;
                    chip.onclick = () => sendMessage(option.text);
                    chipContainer.appendChild(chip);
                });
                
                chatBody.appendChild(chipContainer);
            } else {
                addMessageToDOM(item.message, item.sender);
            }
        });
        chatBody.scrollTop = chatBody.scrollHeight;
    }
}

function loadWelcome() {
    fetch('/chatbot/welcome')
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                console.error('Welcome error:', data.error);
                addMessage('Halo! Ada yang bisa saya bantu?', 'bot');
            } else {
                renderMessages(data);
            }
            updateChatTimestamp();
            startInactivityTimer();
        })
        .catch(err => {
            console.error('Fetch error:', err);
            addMessage('Halo! Ada yang bisa saya bantu?', 'bot');
            updateChatTimestamp();
            startInactivityTimer();
        });
}

function sendMessage(text = null) {
    const message = text ?? userInput.value;
    if (!message) return;

    addMessage(message, 'user');
    userInput.value = '';
    
    // Reset timer karena ada aktivitas
    resetInactivityTimer();

    fetch('/chatbot/send', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document
                .querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ message })
    })
    .then(res => res.json())
    .then(data => {
        if (data.error) {
            console.error('Send error:', data.error);
        }
        
        if (data.payload && data.payload.length > 0) {
            renderMessages(data.payload);
            
            // Check if bot doesn't understand the question
            let hasUnknownResponse = false;
            let botResponse = '';
            
            data.payload.forEach(msg => {
                if (msg.type === 'text' && msg.text) {
                    msg.text.forEach(text => {
                        botResponse += text + ' ';
                        if (text.includes('Mohon maaf, NotaryBot belum memahami pertanyaan Anda')) {
                            hasUnknownResponse = true;
                        }
                    });
                }
            });
            
            if (hasUnknownResponse) {
                saveUnansweredQuestion(message, botResponse.trim());
            }
        } else if (data.reply) {
            addMessage(data.reply, 'bot');
            
            // Check if bot doesn't understand the question
            if (data.reply.includes('Mohon maaf, NotaryBot belum memahami pertanyaan Anda')) {
                saveUnansweredQuestion(message, data.reply);
            }
        }
        updateChatTimestamp();
        resetInactivityTimer();
    })
    .catch(err => {
        console.error('Fetch error:', err);
        addMessage('Maaf, terjadi kesalahan. Silakan coba lagi.', 'bot');
        updateChatTimestamp();
        resetInactivityTimer();
    });
}

function addMessageToDOM(text, sender) {
    const div = document.createElement('div');
    div.className = `message ${sender}`;
    div.innerText = text;
    chatBody.appendChild(div);
    chatBody.scrollTop = chatBody.scrollHeight;
}

function addMessage(text, sender) {
    addMessageToDOM(text, sender);
    saveChatHistory(text, sender, false);
}

function renderMessages(messages) {
    if (!messages || messages.length === 0) return;

    messages.forEach(msg => {
        if (msg.type === 'text' && msg.text) {
            msg.text.forEach(t => addMessage(t, 'bot'));
        }

        if (msg.type === 'payload' && msg.payload) {
            if (msg.payload.richContent && Array.isArray(msg.payload.richContent)) {
                msg.payload.richContent.forEach(section => {
                    section.forEach(item => {
                        if (item.type === 'chips' && item.options) {
                            const chipContainer = document.createElement('div');
                            chipContainer.className = 'chips';

                            item.options.forEach(option => {
                                const chip = document.createElement('div');
                                chip.className = 'chip';
                                chip.innerText = option.text;
                                chip.onclick = () => sendMessage(option.text);
                                chipContainer.appendChild(chip);
                            });

                            chatBody.appendChild(chipContainer);
                            saveChatHistory(item.options, 'bot', true);
                        }
                    });
                });
            }
        }
    });
    
    chatBody.scrollTop = chatBody.scrollHeight;
}

function saveUnansweredQuestion(question, botResponse) {
    fetch('/chatbot/unanswered-question', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ 
            question: question,
            bot_response: botResponse
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            console.log('Unanswered question saved:', data);
        }
    })
    .catch(err => {
        console.error('Error saving unanswered question:', err);
    });
}

