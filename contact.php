<?php session_start(); ?>
<?php include 'includes/header.php'; ?>

<!-- Hero Section for Contact Page -->
<section class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden bg-cover bg-center" style="background-image: url('uploads/placeholders/contact.jpg');">
    <!-- Overlay for better text readability -->
    <div class="absolute inset-0 bg-blue-950/70"></div>
    
    <div class="container mx-auto px-6 text-center relative z-10 min-h-[calc(50vh)] flex flex-col justify-center items-center">
        <h1 class="text-4xl md:text-6xl lg:text-7xl font-bold text-white mb-6 tracking-tight leading-tight">
            Get in <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-orange-500">Touch</span>
        </h1>
        <p class="text-lg text-blue-200 mb-10 max-w-2xl mx-auto">
            We're here to help. Reach out to our team and let's discuss how we can support your business.
        </p>
    </div>
</section>

<!-- Contact Details Section (Starts here, adjust padding as needed) -->
<section class="py-20 bg-blue-950">
    <div class="container mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
            <!-- Call Us -->
            <div class="bg-blue-900 p-8 rounded-lg border border-blue-800 hover:border-orange-500/50 transition duration-300">
                <div class="flex items-center gap-4 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-phone text-orange-500"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <div>
                        <h3 class="text-xl font-bold text-white">Call Us Today</h3>
                    </div>
                </div>
                <div class="space-y-2">
                    <a href="tel:+2349161212301" class="block text-blue-200 hover:text-orange-400 transition font-semibold">+234 916 121 2301</a>
                    <a href="tel:+2348066113394" class="block text-blue-200 hover:text-orange-400 transition font-semibold">+234 806 611 3394</a>
                </div>
            </div>

            <!-- Email Us -->
            <div class="bg-blue-900 p-8 rounded-lg border border-blue-800 hover:border-orange-500/50 transition duration-300">
                <div class="flex items-center gap-4 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mail text-orange-500"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    <div>
                        <h3 class="text-xl font-bold text-white">Send Us an Email</h3>
                    </div>
                </div>
                <div class="space-y-2">
                    <a href="mailto:sales@ddbuildingtech.com" class="block text-blue-200 hover:text-orange-400 transition font-semibold">sales@ddbuildingtech.com</a>
                    <a href="mailto:support@ddbuildingtech.com" class="block text-blue-200 hover:text-orange-400 transition font-semibold">support@ddbuildingtech.com</a>
                </div>
            </div>

            <!-- Visit Us -->
            <div class="bg-blue-900 p-8 rounded-lg border border-blue-800 hover:border-orange-500/50 transition duration-300">
                <div class="flex items-center gap-4 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin text-orange-500"><path d="M20 10c0 7-7.07 11.3-7.07 11.3S6 17 6 10a7 7 0 1 1 14 0Z"/><circle cx="13" cy="10" r="3"/></svg>
                    <div>
                        <h3 class="text-xl font-bold text-white">Corporate HQ</h3>
                    </div>
                </div>
                <p class="text-blue-200 leading-relaxed">
                    98,ogui Road,Opposite stadium gate,Enugu
                </p>
                <p class="text-blue-200 leading-relaxed mt-4">
                    Elite plaza, Behind GIGM park Unizik junction,<br/>
                    Along Enugu-Onitsha Expressway,<br/>
                    Awka, Nigeria
                </p>
            </div>
        </div>

        <!-- WhatsApp Community -->
        <div class="bg-gradient-to-r from-blue-900 to-blue-800 p-12 rounded-lg border border-blue-700 text-center">
            <h2 class="text-2xl font-bold text-white mb-4">Join Our WhatsApp Community</h2>
            <p class="text-blue-200 mb-6 max-w-2xl mx-auto">Connect with our team and other clients in our active WhatsApp community for updates, support, and announcements.</p>
            <a href="https://chat.whatsapp.com/DANNeORxeTR6idjKJl32mE" target="_blank" class="inline-flex items-center gap-2 px-8 py-4 bg-orange-500 hover:bg-orange-400 text-white font-bold rounded hover:scale-105 transition transform duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421-7.403h-.004a9.87 9.87 0 00-4.871 1.235c-1.5.909-2.818 2.385-3.646 4.059-1.038 2.067-.67 4.469.916 6.053 1.585 1.586 3.989 1.953 6.053.916 1.674-.827 3.15-2.146 4.059-3.646 1.235-2.407.652-5.359-1.235-6.871-.915-.719-2.055-1.146-3.272-1.146zm0 2.452a2.504 2.504 0 012.505 2.505c0 1.382-1.123 2.505-2.505 2.505-1.382 0-2.505-1.123-2.505-2.505 0-1.382 1.123-2.505 2.505-2.505z" style="fill: white;"/></svg>
                Join WhatsApp Community
            </a>
        </div>

        <!-- Social Media -->
        <div class="bg-blue-900 p-12 rounded-lg border border-blue-800 text-center mb-16">
            <h2 class="text-2xl font-bold text-white mb-4">Connect with Us</h2>
            <p class="text-blue-200 mb-6 max-w-2xl mx-auto">Follow us on social media for the latest updates, projects, and insights.</p>
            <div class="flex justify-center gap-6 text-blue-300">
                <a href="https://www.linkedin.com/company/dd-buildingtech/" target="_blank" class="hover:text-orange-400 transition transform hover:scale-110">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-linkedin"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect width="4" height="12" x="2" y="9"/><circle cx="4" cy="4" r="2"/></svg>
                </a>
                <a href="https://www.instagram.com/ddbuildingtech?igsh=MW05bm9pMGExdm50Yw==" target="_blank" class="hover:text-orange-400 transition transform hover:scale-110">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-instagram"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                </a>
                <a href="https://www.facebook.com/share/1ALMJGomiq/" target="_blank" class="hover:text-orange-400 transition transform hover:scale-110">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-facebook"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                </a>
                <a href="https://youtube.com/@ddbuildingtech?si=lSbBjA-0l4ZGlCWf" target="_blank" class="hover:text-orange-400 transition transform hover:scale-110">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-youtube"><path d="M2.5 17.592a4.93 4.93 0 0 1-.502-1.464c-.097-.732-.095-2.007-.095-2.007S2 9.07 2 7.824a4.898 4.898 0 0 1 .494-1.455C3.033 5.4 4.09 4.904 6.702 4.904h.023c2.617 0 5.234 0 7.852 0h.023c2.617 0 3.674.502 4.204 1.465a4.93 4.93 0 0 1 .502 1.464c.097.732.095 2.007.095 2.007s.002 1.275.002 2.52c0 1.246-.002 2.52-.002 2.52s-.002 1.275-.095 2.007a4.93 4.93 0 0 1-.502 1.464c-.53.963-1.587 1.465-4.204 1.465h-.023c-2.617 0-5.234 0-7.852 0h-.023c-2.617 0-3.674-.502-4.204-1.465Z"/><path d="m10.875 8.922 4.995 2.503-4.995 2.503V8.922Z"/></svg>
                </a>
                <a href="https://www.tiktok.com/@ddbuildingtech?_r=1&_t=ZS-9252xey2g88" target="_blank" class="hover:text-orange-400 transition transform hover:scale-110">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-tiktok"><path d="M9 12d-4 0-4 4 0 4-4 0c0-4 4-4 4-4z"/><path d="M12 9v12"/><path d="M20 9V12A4 4 0 0 1 16 16"/><path d="M16 12h4"/></svg>
                </a>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
