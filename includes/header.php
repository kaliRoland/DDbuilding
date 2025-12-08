<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DDbuilding Tech - Advanced Systems</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide-react@0.292.0/dist/lucide-react.js"></script>
    <script>
        // Custom Tailwind config with brand colors
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
                    },
                    colors: {
                        slate: {
                            850: '#1e3a5f', // Custom darker blue
                        },
                        brand: {
                            blue: '#1e3a8a',
                            'blue-light': '#3b82f6',
                            'blue-dark': '#0f172a',
                            white: '#ffffff',
                            orange: '#f97316'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #0f172a; 
        }
        ::-webkit-scrollbar-thumb {
            background: #3b82f6; 
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #f97316; 
        }
        
        /* Hide number input arrows */
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
    </style>
</head>
<body class="bg-blue-950 text-slate-100 font-sans antialiased selection:bg-orange-500 selection:text-white">

    <!-- Floating Header with Glassmorphism -->
    <header class="fixed w-full top-0 z-50 bg-white shadow-md">
        <nav class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <!-- Logo -->
                <a href="index.php" class="flex items-center gap-2 group">
                    <img src="uploads/logo/logo.png" alt="DDbuildingTech Logo" class="h-8">
                </a>

                <!-- Desktop Nav -->
                <div class="hidden md:flex items-center space-x-8 font-medium text-sm uppercase tracking-widest text-blue-900">
                    <a href="index.php" class="hover:text-orange-500 transition-colors duration-300">Home</a>
                    <a href="products.php" class="hover:text-orange-500 transition-colors duration-300">Shop</a>
                    <a href="blog.php" class="hover:text-orange-500 transition-colors duration-300">Blog</a>
                    <a href="gallery.php" class="hover:text-orange-500 transition-colors duration-300">Gallery</a>
                    <a href="#" class="hover:text-orange-500 transition-colors duration-300">Solutions</a>
                    <a href="#" class="hover:text-orange-500 transition-colors duration-300">Support</a>
                </div>

                <!-- Actions -->
                <div class="flex items-center space-x-5">
                     <button class="hidden md:block text-blue-900 hover:text-orange-500 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </button>

                    <button id="cart-button" class="group flex items-center gap-2 text-blue-900 hover:text-orange-500 transition">
                        <div class="relative">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-bag"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            <span id="cart-count" class="absolute -top-2 -right-2 bg-orange-500 text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center">0</span>
                        </div>
                        <span class="hidden lg:block text-sm font-semibold group-hover:text-orange-500 transition">Cart</span>
                    </button>

                     <!-- Mobile Menu Button -->
                     <button id="mobile-menu-button" class="md:hidden text-blue-900 hover:text-orange-500">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-menu"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                    </button>
                </div>
            </div>
             <!-- Mobile Menu -->
             <div id="mobile-menu" class="hidden md:hidden mt-4 border-t border-blue-200 pt-4">
                <a href="#" class="block py-2 text-blue-900 hover:text-orange-500">Equipment</a>
                <a href="products.php" class="block py-2 text-blue-900 hover:text-orange-500">Shop</a>
                <a href="blog.php" class="block py-2 text-blue-900 hover:text-orange-500">Blog</a>
                <a href="gallery.php" class="block py-2 text-blue-900 hover:text-orange-500">Gallery</a>
                <a href="#" class="block py-2 text-blue-900 hover:text-orange-500">Solutions</a>
                <a href="#" class="block py-2 text-blue-900 hover:text-orange-500">Support</a>
            </div>
        </nav>
    </header>