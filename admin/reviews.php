<?php
require_once __DIR__ . '/includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

log_activity($conn, $_SESSION['admin_id'], 'view_reviews');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reviews</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/brand.css">
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-5NVJVRF7');</script>
    <!-- End Google Tag Manager -->
</head>
<body class="bg-slate-900 text-slate-100">
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5NVJVRF7"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->

    <div class="flex min-h-screen">
        <?php include 'includes/sidebar.php'; ?>

        <main class="flex-1 p-10">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-3xl font-bold text-white">Manage Reviews</h1>
                <div class="flex gap-3">
                    <button data-status="pending" class="status-btn bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold py-2 px-4 rounded transition">Pending</button>
                    <button data-status="approved" class="status-btn bg-slate-700 hover:bg-slate-600 text-white font-bold py-2 px-4 rounded transition">Approved</button>
                </div>
            </div>

            <div class="bg-slate-800 rounded-lg overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-slate-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Reviewer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Rating</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Review</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="reviews-tbody" class="divide-y divide-slate-700">
                        <!-- Reviews will be loaded here -->
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const API_URL = 'api.php';
            const tbody = document.getElementById('reviews-tbody');
            const statusButtons = document.querySelectorAll('.status-btn');
            let currentStatus = 'pending';

            const escapeHTML = (str) => {
                if (str === null || str === undefined) return '';
                const p = document.createElement('p');
                p.appendChild(document.createTextNode(str));
                return p.innerHTML;
            };

            const renderStars = (rating) => {
                const r = parseInt(rating || 0, 10);
                let stars = '';
                for (let i = 1; i <= 5; i++) {
                    stars += i <= r ? '&#9733;' : '&#9734;';
                }
                return `<span class="text-amber-400 tracking-wide">${stars}</span>`;
            };

            const renderReviews = (reviews) => {
                tbody.innerHTML = '';
                if (!reviews || reviews.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-slate-500">No reviews found.</td></tr>';
                    return;
                }
                reviews.forEach(review => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="px-6 py-4 text-sm text-white">${escapeHTML(review.product_name)}</td>
                        <td class="px-6 py-4 text-sm text-slate-300">${escapeHTML(review.name)}</td>
                        <td class="px-6 py-4 text-sm">${renderStars(review.rating)}</td>
                        <td class="px-6 py-4 text-sm text-slate-300">${escapeHTML(review.review_text)}</td>
                        <td class="px-6 py-4 text-sm text-slate-400">${escapeHTML(review.created_at_formatted)}</td>
                        <td class="px-6 py-4 text-right text-sm">
                            ${currentStatus === 'pending' ? `<button class="approve-btn text-emerald-400 hover:text-emerald-300 mr-3" data-id="${review.id}">Approve</button>` : ''}
                            <button class="delete-btn text-red-400 hover:text-red-300" data-id="${review.id}">Delete</button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            };

            const fetchReviews = async () => {
                try {
                    const response = await fetch(`${API_URL}?action=get_reviews&status=${currentStatus}`);
                    const data = await response.json();
                    if (data.status === 'success') {
                        renderReviews(data.reviews);
                    } else {
                        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-8 text-red-400">${escapeHTML(data.message || 'Failed to load reviews.')}</td></tr>`;
                    }
                } catch (error) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-red-400">Failed to load reviews.</td></tr>';
                }
            };

            const approveReview = async (id) => {
                const formData = new FormData();
                formData.append('id', id);
                const response = await fetch(`${API_URL}?action=approve_review`, { method: 'POST', body: formData });
                const data = await response.json();
                if (data.status === 'success') {
                    fetchReviews();
                } else {
                    alert(data.message || 'Failed to approve review.');
                }
            };

            const deleteReview = async (id) => {
                if (!confirm('Delete this review?')) return;
                const formData = new FormData();
                formData.append('id', id);
                const response = await fetch(`${API_URL}?action=delete_review`, { method: 'POST', body: formData });
                const data = await response.json();
                if (data.status === 'success') {
                    fetchReviews();
                } else {
                    alert(data.message || 'Failed to delete review.');
                }
            };

            statusButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    currentStatus = btn.dataset.status;
                    statusButtons.forEach(b => b.classList.remove('bg-amber-500', 'text-slate-900'));
                    statusButtons.forEach(b => b.classList.add('bg-slate-700', 'text-white'));
                    btn.classList.remove('bg-slate-700', 'text-white');
                    btn.classList.add('bg-amber-500', 'text-slate-900');
                    fetchReviews();
                });
            });

            tbody.addEventListener('click', (e) => {
                const approveBtn = e.target.closest('.approve-btn');
                const deleteBtn = e.target.closest('.delete-btn');
                if (approveBtn) approveReview(approveBtn.dataset.id);
                if (deleteBtn) deleteReview(deleteBtn.dataset.id);
            });

            fetchReviews();
        });
    </script>
</body>
</html>



