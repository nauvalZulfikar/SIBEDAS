import GlobalConfig from "../global-config.js";

document.addEventListener("DOMContentLoaded", function () {
    const timeElements = document.querySelectorAll(".sending-message-time p");

    timeElements.forEach((element) => {
        element.textContent = getCurrentTime();
    });

    const textarea = document.getElementById("user-message");
    const sendButton = document.getElementById("send");
    const conversationArea = document.querySelector(".row.flex-grow");
    const chatHistory = [];

    // Fungsi untuk mengirim pesan
    async function sendMessage() {
        const userText = textarea.value.trim();
        if (userText !== "") {
            // Kosongkan textarea setelah mengirim
            textarea.value = "";

            // Tambahkan pesan user ke UI
            addMessage(userText, "user");

            // Tambahkan pesan bot sementara dengan "Loading..."
            const botMessageElement = addMessage('<div class="bot-message-text">...</div>', "bot");

            const messageTextContainer = botMessageElement.querySelector(".bot-message-text");
            if (messageTextContainer) {
                messageTextContainer.innerHTML = '<div class="loader ms-3"></div>';
            }

            // Panggil API untuk mendapatkan respons dari bot
            const botResponse = await getBotResponse(userText, chatHistory);

            // Perbarui pesan bot dengan respons yang sebenarnya
            if (messageTextContainer) {
                messageTextContainer.innerHTML = botResponse;
            }
        }
    }

    // Event listener untuk klik tombol
    sendButton.addEventListener("click", sendMessage);

    // Event listener untuk menekan Enter di textarea
    textarea.addEventListener("keydown", function (event) {
        if (event.key === "Enter" && !event.shiftKey) {
            event.preventDefault(); // Mencegah newline di textarea
            sendMessage(); // Panggil fungsi kirim pesan
        }
    });

    function getCurrentTime() {
        const now = new Date();
        return now.getHours().toString().padStart(2, "0") + ":" + now.getMinutes().toString().padStart(2, "0");
    }

    function addMessage(text, sender) {
        const messageRow = document.createElement("div");
        // Atur posisi berdasarkan sender (user -> end, bot -> start)
        messageRow.classList.add("row", "flex-grow", "overflow-auto", sender === "user" ? "justify-content-end" : "justify-content-start");
    
        const messageCol = document.createElement("div");
        messageCol.classList.add("col-9", "w-auto");
    
        // Atur lebar maksimum berdasarkan sender
        messageCol.style.maxWidth = sender === "user" ? "50%" : "75%";
    
        // Container untuk menyimpan nama dan bubble chat
        const messageWrapper = document.createElement("div");
        messageWrapper.classList.add("d-flex", "flex-column");
    
        // Tambahkan Nama di luar bubble chat
        const messageName = document.createElement("p");
        messageName.classList.add("fw-bolder", sender === "user" ? "text-end" : "text-start", "mb-1");
        messageName.textContent = sender === "user" ? "You" : "Neng Bedas";
    
        // Bubble Chat
        const messageContainer = document.createElement("div");
        messageContainer.classList.add("p-2", "rounded", "mb-2", "d-inline-block");
        if (sender === "user") {
            messageContainer.classList.add("user-response", "bg-primary", "text-white");
        } else {
            messageContainer.classList.add("bot-response", "bg-light");
        }
    
        const messageContent = document.createElement("div");
        messageContent.classList.add("bot-message-text", "mb-0", "text-start");
        messageContent.textContent = text;
    
        // Waktu di dalam bubble chat
        const messageTime = document.createElement("div");
        messageTime.classList.add("sending-message-time", "text-end", "mt-1");
        messageTime.innerHTML = `<p class="small mb-0 ${sender === "user" ? "text-white text-start" : "text-muted"}">${getCurrentTime()}</p>`;
    
        messageContainer.appendChild(messageContent);
        messageContainer.appendChild(messageTime);
    
        // Jika pengirim adalah bot, tambahkan avatar
        if (sender !== "user") {
            const avatarContainer = document.createElement("div");
            avatarContainer.classList.add("col-auto", "pe-0");
    
            const avatarImg = document.createElement("img");
            avatarImg.classList.add("rounded-circle");
            avatarImg.width = 45;
            avatarImg.src = "/images/iconchatbot.jpeg";
            avatarImg.alt = "bot-avatar";
    
            avatarContainer.appendChild(avatarImg);
            messageRow.appendChild(avatarContainer);
        }
    
        // Masukkan nama dan bubble ke dalam wrapper
        messageWrapper.appendChild(messageName);
        messageWrapper.appendChild(messageContainer);
        messageCol.appendChild(messageWrapper);
        messageRow.appendChild(messageCol);
    
        conversationArea.appendChild(messageRow);
        conversationArea.scrollTop = conversationArea.scrollHeight;
    
        return messageContainer;
    } 

    // Fungsi untuk memanggil API
    async function getBotResponse(userText, historyChat) {
        try {
            const url = `${GlobalConfig.apiHost}/api/main-generate-text`;
            const response = await fetch(url, {
                method: "POST",
                body: JSON.stringify({prompt: userText,  chatHistory: historyChat}),
                headers: {
                    Authorization: `Bearer ${document
                        .querySelector('meta[name="api-token"]')
                        .getAttribute("content")}`,
                    "Content-Type": "application/json",
                },
            });

            const data = await response.json();
            const rawBotResponse = data.nlpResponse;
            // Tambahkan ke chatHistory
            chatHistory.push({
                user: userText,
                rawBotResponse: rawBotResponse,
            });
            return data.response || "Maaf, saya tidak mengerti.";
        } catch (error) {
            console.error("Error fetching bot response:", error);
            return "Terjadi kesalahan, coba lagi nanti.";
        }
    }
});
