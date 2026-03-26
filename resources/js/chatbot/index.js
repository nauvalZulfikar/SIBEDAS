import GlobalConfig from "../global-config.js";

document.addEventListener("DOMContentLoaded", function () {
    const tabs = document.querySelectorAll(".nav-link");
    const timeElements = document.querySelectorAll(".sending-message-time p");

    timeElements.forEach((element) => {
        element.textContent = getCurrentTime();
    });

    function activateTab(tab) {
        tabs.forEach(btn => {
            btn.classList.remove("border-3", "bg-primary", "text-white"); // Reset semua tab
        });
        tab.classList.add("border-3", "bg-primary", "text-white"); // Tambahkan warna pada tab aktif
    }

    tabs.forEach(tab => {
        tab.addEventListener("click", function () {
            activateTab(this);
        });
    });

    // Set warna awal untuk tab aktif (jika ada)
    const initialActiveTab = document.querySelector(".nav-link.active");
    if (initialActiveTab) {
        activateTab(initialActiveTab);
    }

    document.querySelectorAll(".nav-link").forEach(tab => {
        tab.addEventListener("click", function () {
            setTimeout(() => {
                const tab_active = getActiveTabId();
                console.log("Active Tab ID:", tab_active);

                // Hapus semua chat kecuali pesan default bot
                conversationArea.innerHTML = `
                <div class="row flex-grow overflow-auto align-items-start">
                    <!-- Avatar -->
                    <div class="col-auto alignpe-0">
                        <img class="rounded-circle" width="45" src="/images/iconchatbot.jpeg" alt="avatar-3">
                    </div>

                    <!-- Nama dan Bubble Chat -->
                    <div class="col-9 w-auto">
                        <!-- Nama Bot -->
                        <p class="fw-bolder mb-1">Neng Bedas</p>

                        <!-- Bubble Chat -->
                        <div class="bot-response p-2 bg-light rounded mb-2 d-inline-block">
                            <p class="mb-0">Halo! Ada yang bisa saya bantu?</p>

                            <!-- Waktu (Tetap di Dalam Bubble Chat) -->
                            <div class="sending-message-time text-end mt-1">
                                <p class="text-muted small mb-0">Now</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            }, 100); // Timeout untuk memastikan class `active` sudah diperbarui
        });
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

            // Ambil tab aktif saat ini
            const currentTab = getActiveTabId();

            // Tambahkan pesan user ke UI
            addMessage(userText, "user");

            // Tambahkan pesan bot sementara dengan "Loading..."
            const botMessageElement = addMessage('<div class="bot-message-text">...</div>', "bot");

            const messageTextContainer = botMessageElement.querySelector(".bot-message-text");
            if (messageTextContainer) {
                messageTextContainer.innerHTML = '<div class="loader ms-3"></div>';
            }

            // Panggil API untuk mendapatkan respons dari bot
            const botResponse = await getBotResponse(currentTab, userText, chatHistory);

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
    async function getBotResponse(tab_active, userText, historyChat) {
        try {
            const url = `${GlobalConfig.apiHost}/api/generate-text`;
            const response = await fetch(url, {
                method: "POST",
                body: JSON.stringify({tab_active:tab_active, prompt: userText, chatHistory: historyChat }),
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

function getActiveTabId() {
    const activeTab = document.querySelector(".nav-link.active");
    return activeTab ? activeTab.id : null;
}
