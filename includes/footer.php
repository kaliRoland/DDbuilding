    <!-- Footer -->
    <footer class="bg-blue-950 border-t border-blue-800 pt-16 pb-8">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                <div class="col-span-1 md:col-span-1">
                    <a href="#" class="flex items-center gap-2 mb-4">
                        <img src="uploads/logo/logo.png" alt="DDbuildingTech Logo" class="h-6">
                    </a>
                                        <p class="text-blue-300 text-sm leading-relaxed">
                                            Empowering homes and businesses with sustainable energy solutions and military-grade security systems.
                                        </p>
                                    </div>
                                    <div>
                                        <h4 class="text-white font-semibold mb-4">Shop</h4>
                                        <ul class="space-y-2 text-blue-300 text-sm">
                                            <li><a href="#" class="hover:text-orange-400 transition">Solar Panels</a></li>
                                            <li><a href="#" class="hover:text-orange-400 transition">Inverters</a></li>
                                            <li><a href="#" class="hover:text-orange-400 transition">Batteries</a></li>
                                            <li><a href="#" class="hover:text-orange-400 transition">CCTV Kits</a></li>
                                        </ul>
                                    </div>
                                    <div>
                                        <h4 class="text-white font-semibold mb-4">Company</h4>
                                        <ul class="space-y-2 text-blue-300 text-sm">
                                            <li><a href="#" class="hover:text-orange-400 transition">About Us</a></li>
                                            <li><a href="#" class="hover:text-orange-400 transition">Careers</a></li>
                                            <li><a href="#" class="hover:text-orange-400 transition">Installers</a></li>
                                            <li><a href="#" class="hover:text-orange-400 transition">Contact</a></li>
                                        </ul>
                                    </div>
                                    <div>
                                        <h4 class="text-white font-semibold mb-4">Stay Connected</h4>
                                        <div class="flex gap-2 mb-4">
                                            <input type="email" placeholder="Enter email address" class="bg-blue-900 border border-blue-800 text-white text-sm rounded px-3 py-2 w-full focus:outline-none focus:border-orange-500">
                                            <button class="bg-orange-500 hover:bg-orange-600 text-white rounded px-3 py-2 transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                            </button>
                                        </div>
                                        <div class="flex gap-4 text-blue-300">
                                            <a href="#" class="hover:text-white transition"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-twitter"><path d="M22 4s-.7 2.1-2 3.4c1.6 1.4 3.3 4.9 3.3 4.9s-5.2-.1-7.2-2.3c-1.4 1.1-2.8 2.3-4.3 2.3s-2.8-1.2-4.3-2.3C5.1 12.4 0 12.3 0 12.3s1.7-3.6 3.3-4.9C1.3 6.1.7 4 .7 4s2.1.7 3.6 1.8C6.1 4.5 9.1 4 12 4s5.9.5 7.7 1.8C21.3 4.7 22 4 22 4z"/></svg></a>
                                            <a href="#" class="hover:text-white transition"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-linkedin"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect width="4" height="12" x="2" y="9"/><circle cx="4" cy="4" r="2"/></svg></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="border-t border-blue-900 pt-8 text-center text-blue-400 text-sm">
                                    &copy; 2025 <img src="uploads/logo/logo.png" alt="DDbuildingTech Logo" class="h-4 inline-block mx-1">. Built for the future.            </div>
        </div>
    </footer>

    <!-- Cart Slide-over -->
    <div id="cart-modal" class="fixed inset-0 z-50 hidden">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity opacity-0" id="cart-backdrop"></div>
        
        <!-- Panel -->
        <div class="absolute inset-y-0 right-0 flex max-w-full pl-10">
            <div class="w-screen max-w-md transform transition ease-in-out duration-500 translate-x-full" id="cart-panel">
                <div class="h-full flex flex-col bg-slate-900 shadow-2xl border-l border-slate-800">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800">
                        <h2 class="text-lg font-bold text-white">Shopping Cart</h2>
                        <button id="close-cart-button" class="text-slate-400 hover:text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        </button>
                    </div>

                    <!-- Items -->
                    <div id="cart-items-container" class="flex-1 overflow-y-auto p-6 space-y-6 cart-items">
                        <div id="empty-cart-message" class="flex flex-col items-center justify-center h-64 text-slate-500">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-cart mb-4 opacity-50"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                            <p>Your cart is currently empty.</p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="border-t border-slate-800 p-6 bg-slate-850">
                        <div class="flex justify-between text-base font-medium text-white mb-4">
                            <p>Subtotal</p>
                            <p id="cart-subtotal">$0.00</p>
                        </div>
                        <p class="mt-0.5 text-sm text-slate-500 mb-6">Shipping and taxes calculated at checkout.</p>
                        <button id="checkout-button" disabled class="w-full flex items-center justify-center rounded-md border border-transparent bg-amber-500 px-6 py-3 text-base font-bold text-slate-900 shadow-sm hover:bg-amber-400 disabled:opacity-50 disabled:cursor-not-allowed transition">
                            Checkout
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script src="js/main.js"></script>

    <?php if (basename($_SERVER['PHP_SELF']) == 'blog.php'): ?>
    <script src="js/blog.js"></script>
    <?php endif; ?>

    <?php if (basename($_SERVER['PHP_SELF']) == 'gallery.php'): ?>
    <script src="js/gallery.js"></script>
    <?php endif; ?>

    <!-- AI Chat Button -->
    <button id="ai-chat-button" class="fixed bottom-6 right-6 bg-amber-500 hover:bg-amber-400 text-slate-900 font-bold py-4 px-4 rounded-full shadow-lg hover:scale-110 transition transform duration-200 z-50">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bot"><path d="M12 8V4H8L4 8.5V16.5A2.5 2.5 0 0 0 6.5 19H8"/><path d="M12 8h4L20 12v4.5A2.5 2.5 0 0 1 17.5 19H16"/><rect width="8" height="4" x="8" y="12" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 7v1"/><path d="M9 7v1"/></svg>
    </button>

    <!-- AI Chat Modal -->
    <div id="ai-chat-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-40 hidden">
        <div class="bg-slate-800 rounded-lg shadow-xl w-full max-w-lg mx-4 flex flex-col" style="height: 70vh;">
            <div class="flex justify-between items-center p-4 border-b border-slate-700">
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bot text-amber-400"><path d="M12 8V4H8L4 8.5V16.5A2.5 2.5 0 0 0 6.5 19H8"/><path d="M12 8h4L20 12v4.5A2.5 2.5 0 0 1 17.5 19H16"/><rect width="8" height="4" x="8" y="12" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 7v1"/><path d="M9 7v1"/></svg>
                    Chat with Don
                </h3>
                <button id="close-ai-chat-button" class="text-slate-400 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            <div id="ai-chat-messages" class="p-4 flex-1 overflow-y-auto">
                <!-- Chat messages will be appended here -->
            </div>
            <div class="p-4 border-t border-slate-700">
                <div class="flex gap-2">
                    <input type="text" id="ai-chat-input" placeholder="Tell me what you're looking for..." class="w-full bg-slate-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent transition">
                    <button id="ai-chat-send-button" class="bg-amber-500 hover:bg-amber-400 text-slate-900 font-bold py-2 px-4 rounded-lg transition">Send</button>
                </div>
            </div>
        </div>
    </div>

    <style>
    .chat-message {
        margin-bottom: 1rem;
        display: flex;
    }
    .user-message {
        justify-content: flex-end;
    }
    .bot-message {
        justify-content: flex-start;
    }
    .message-content {
        max-width: 80%;
        padding: 0.75rem 1rem;
        border-radius: 0.75rem;
        font-size: 0.9rem;
        line-height: 1.5;
    }
    .user-message .message-content {
        background-color: #fbbf24;
        color: #1e293b;
    }
    .bot-message .message-content {
        background-color: #334155;
        color: #f1f5f9;
    }
    </style>

    <script src="js/chat.js"></script>
</body>
</html>