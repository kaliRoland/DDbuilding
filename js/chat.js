document.addEventListener('DOMContentLoaded', () => {
    const chatButton = document.getElementById('ai-chat-button');
    const chatModal = document.getElementById('ai-chat-modal');
    const closeChatButton = document.getElementById('close-ai-chat-button');
    const sendButton = document.getElementById('ai-chat-send-button');
    const chatInput = document.getElementById('ai-chat-input');
    const messagesContainer = document.getElementById('ai-chat-messages');

    let products = [];

    // --- UI Controls ---
    chatButton.addEventListener('click', () => {
        console.log('Chat button clicked');
        chatModal.classList.remove('hidden');
        if (products.length === 0) {
            // First time opening, fetch products and show welcome message
            fetchProductsAndWelcome();
        }
    });

    closeChatButton.addEventListener('click', () => {
        chatModal.classList.add('hidden');
    });

    // --- Chat Logic ---
    async function fetchProductsAndWelcome() {
        console.log('Fetching products...');
        try {
            const response = await fetch('api/products.php?action=get_all');
            const data = await response.json();
            if (data.status === 'success') {
                products = data.products;
                console.log('Products fetched:', products);
                appendMessage("Hi, I'm Don! How can I help you find the perfect product today?", 'bot');
            } else {
                console.error('API error while fetching products:', data.message);
                appendMessage("Sorry, I'm having trouble connecting to our product database right now.", 'bot');
            }
        } catch (error) {
            console.error("Failed to fetch products for chat:", error);
            appendMessage("Sorry, I'm having trouble connecting. Please try again later.", 'bot');
        }
    }

    function appendMessage(html, sender) {
        console.log('Appending message:', {html, sender});
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('chat-message', sender === 'user' ? 'user-message' : 'bot-message');
        
        const messageContent = document.createElement('div');
        messageContent.classList.add('message-content');
        messageContent.innerHTML = html;
        
        messageDiv.appendChild(messageContent);
        messagesContainer.appendChild(messageDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    function recommendProducts(query) {
        console.log('Recommending products for query:', query);
        const queryLower = query.toLowerCase();
        const keywords = queryLower.split(' ').filter(w => w.length > 2);

        const matchedProducts = products.filter(product => {
            const nameLower = product.name.toLowerCase();
            const descLower = product.description ? product.description.toLowerCase() : '';
            return keywords.some(keyword => nameLower.includes(keyword) || descLower.includes(keyword));
        });

        if (matchedProducts.length > 0) {
            console.log('Matched products:', matchedProducts);
            appendMessage("Based on your request, I'd recommend these products:", 'bot');
            matchedProducts.slice(0, 3).forEach(p => {
                const productHTML = `
                    <div class="product-recommendation">
                        <a href="product.php?id=${p.id}" target="_blank" class="flex items-center gap-4 p-2 rounded-lg hover:bg-slate-700 transition">
                            <img src="${p.image_main}" alt="${p.name}" class="w-16 h-16 object-cover rounded">
                            <div class="text-sm">
                                <strong class="text-white">${p.name}</strong>
                                <p class="text-amber-400">NGN ${p.price}</p>
                            </div>
                        </a>
                    </div>
                `;
                appendMessage(productHTML, 'bot');
            });
        } else {
            console.log('No products matched');
            appendMessage("I couldn't find any products that match your description. Can you try being more specific?", 'bot');
        }
    }

    sendButton.addEventListener('click', () => {
        console.log('Send button clicked');
        const message = chatInput.value.trim();
        if (message) {
            appendMessage(message, 'user');
            chatInput.value = '';
            setTimeout(() => recommendProducts(message), 500);
        }
    });

    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            sendButton.click();
        }
    });
});
