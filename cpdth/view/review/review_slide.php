<section class="review-section py-5">
    <div class="container" style="max-width: 1200px;">
        <div class="text-center mb-5">
            <h2 class="fw-bold" style="color: #2e4a82; font-size: 2.2rem;">รีวิวจากผู้เรียนจริง</h2>
            <p class="text-muted fs-5">ผลลัพธ์และความประทับใจจากผู้เรียนของเรา</p>
        </div>
        
        <div class="swiper reviewSwiper" style="padding-bottom: 50px;">
            <div class="swiper-wrapper" id="reviewContainer">
                <!-- Data will be injected here -->
            </div>
            
            <div class="custom-swiper-pagination-wrapper">
                <button class="custom-swiper-prev"><i class="bi bi-chevron-left"></i></button>
                <div class="swiper-pagination"></div>
                <button class="custom-swiper-next"><i class="bi bi-chevron-right"></i></button>
            </div>
        </div>
    </div>
</section>

<style>
.review-section {
    background-color: #ffffff;
}
.review-card {
    background: #fff;
    border: 1px solid #eaeaea;
    border-radius: 8px;
    padding: 30px;
    height: 100%;
    box-shadow: 0 4px 20px rgba(0,0,0,0.03);
    display: flex;
    flex-direction: column;
}
.review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
}
.review-user-info {
    display: flex;
    align-items: center;
    gap: 15px;
}
.review-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
    background-color: #f3f4f6;
}
.review-name {
    font-weight: 600;
    color: #1f2937;
    margin: 0;
    font-size: 1.1rem;
}
.review-role {
    color: #9ca3af;
    margin: 0;
    font-size: 0.9rem;
}
.quote-icon {
    font-size: 3rem;
    color: #e2e8f0;
    line-height: 0.5;
    transform: rotate(180deg);
}
.review-stars {
    color: #f59e0b;
    font-size: 1rem;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.review-date {
    font-size: 0.8rem;
    color: #9ca3af;
}
.review-comment {
    color: #4b5563;
    font-size: 0.95rem;
    line-height: 1.6;
    margin: 0;
    flex-grow: 1;
}

/* Custom Pagination */
.custom-swiper-pagination-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin-top: 30px;
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
}
.swiper-pagination {
    position: static !important;
    width: auto !important;
    display: flex;
    gap: 8px;
}
.swiper-pagination-bullet {
    width: 35px;
    height: 35px;
    background: transparent;
    border: 1px solid #e5e7eb;
    opacity: 1;
    color: #4b5563;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 400;
    margin: 0 !important;
    transition: all 0.2s;
}
.swiper-pagination-bullet:hover {
    background-color: #f3f4f6;
}
.swiper-pagination-bullet-active {
    background-color: #3b5998;
    border-color: #3b5998;
    color: #fff;
}
.swiper-pagination-bullet-active:hover {
    background-color: #3b5998;
}
.custom-swiper-prev, .custom-swiper-next {
    width: 35px;
    height: 35px;
    background: transparent;
    border: 1px solid #e5e7eb;
    color: #4b5563;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
}
.custom-swiper-prev:hover, .custom-swiper-next:hover {
    background-color: #f3f4f6;
}
.custom-swiper-prev.swiper-button-disabled, .custom-swiper-next.swiper-button-disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>

<script>
$(document).ready(function() {
    $.ajax({
        url: 'core.php',
        type: 'POST',
        data: {
            request_state: 'mainReview',
            request_function: 'get_review'
        },
        dataType: 'json',
        success: function(response) {
            if (response.result === 1 && response.data && response.data.length > 0) {
                let html = '';
                response.data.forEach(function(review) {
                    let stars = '';
                    for (let i = 1; i <= 5; i++) {
                        if (i <= review.rating) {
                            stars += '<i class="bi bi-star-fill"></i> ';
                        } else {
                            stars += '<i class="bi bi-star"></i> ';
                        }
                    }
                    
                    html += `
                        <div class="swiper-slide h-auto">
                            <div class="review-card">
                                <div class="review-header">
                                    <div class="review-user-info">
                                        <img src="${review.avatar || 'assets/images/profile-default.jpg'}" class="review-avatar" onerror="this.onerror=null; this.src='assets/images/profile-default.jpg';">
                                        <div>
                                            <p class="review-name">${review.name}</p>
                                            <p class="review-role">${review.role}</p>
                                        </div>
                                    </div>
                                    <div class="quote-icon"><i class="bi bi-quote"></i></div>
                                </div>
                                <div class="review-stars">
                                    <div>${stars}</div>
                                    <div class="review-date">${review.date}</div>
                                </div>
                                <p class="review-comment">"${review.comment}"</p>
                            </div>
                        </div>
                    `;
                });
                $('#reviewContainer').html(html);
                
                // Initialize Swiper
                new Swiper('.reviewSwiper', {
                    slidesPerView: 1,
                    spaceBetween: 20,
                    pagination: {
                        el: '.swiper-pagination',
                        clickable: true,
                        renderBullet: function (index, className) {
                            return '<span class="' + className + '">' + (index + 1) + '</span>';
                        }
                    },
                    navigation: {
                        nextEl: '.custom-swiper-next',
                        prevEl: '.custom-swiper-prev',
                    },
                    breakpoints: {
                        768: {
                            slidesPerView: 2,
                            spaceBetween: 30
                        },
                        1024: {
                            slidesPerView: 3,
                            spaceBetween: 30
                        }
                    }
                });
            } else {
                $('.review-section').hide(); // Hide if no reviews
            }
        },
        error: function() {
            $('.review-section').hide();
        }
    });
});
</script>
