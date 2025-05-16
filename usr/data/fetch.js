function generateStars(rating) {
    let starsHTML = "";
    let fullStars = Math.floor(rating); 
    let halfStar = rating % 1 !== 0; 

    for (let i = 1; i <= 5; i++) {
        if (i <= fullStars) {
            starsHTML += `<i class="fa-solid fa-star"></i> `;
        } else if (halfStar && i === fullStars + 1) {
            starsHTML += `<i class="fa-solid fa-star-half-alt"></i> `;
        } else {
            starsHTML += `<i class="fa-regular fa-star"></i> `;
        }
    }
    return starsHTML;
}
document.addEventListener("DOMContentLoaded", function() {
    const menuItems = document.querySelectorAll("#menu .nav-link");
    menuItems.forEach(item => {
        item.addEventListener("click", function() {
            document.querySelector(".nav-link.selected")?.classList.remove("selected");
    
            this.classList.add("selected");
        });
    });
});

    // --fetch ticket
    fetch('../../server/get_ticket.php') 
        .then(response => { return response.json();})
        .then(data => {
            let ticketList = document.getElementById('ticket-list');
           
            ticketList.innerHTML = "";
            data.forEach(ticket => {
                let starsHTML = generateStars(ticket.rating);
                ticketList.innerHTML += `
                <div class="col-md-6 col-lg-3">
                    <div class="card tour-card">
                        <img src="${ticket.img_url}" class="card-img-top tour-img">
                        <div class="card-body">
                            <h5 class="card-title">${ticket.ticketname}</h5>
                              <p class="text-warning">${starsHTML} (${ticket.rating})</p>
                            <p class="text-danger fw-bold">Giá vé: ${Number(ticket.price).toLocaleString('vi-VN')} VND</p>
                        </div>
                         <a href="detail_tickets.php?id=${ticket.ticket_id}" class="btn btn-buy">Đặt vé</a>
                    </div>
                </div>
            `;
            });
        })
        .catch(error => console.error('Lỗi khi tải dữ liệu:', error));
        

// fetchguider
    fetch('../../server/getguider.php')
        .then(response => {
        return response.json();
    })
    .then(data => {
        let guiderList = document.getElementById('guider-list');
        guiderList.innerHTML = "";
        data.forEach(guider => {
            let starsHTML = generateStars(guider.rating);
            guiderList.innerHTML += `
                <div class="col-md-6 col-lg-3">
                    <div class="card tour-card d-flex flex-column">
                        <img src="${guider.img_url}" class="img-fluid  h-100" alt="${guider.name}">
                        <div class="card-body flex-grow-1 d-flex flex-column">
                            <h5 class="card-title">${guider.name}</h5>
                            <p class="text-warning">${starsHTML} (${guider.rating})</p>
                            <h7 class="text-danger fw-bold">Ngôn ngữ: ${guider.language}</h7>
                        </div>
                        <div>
                            <a href="detail_guide.php?id=${guider.guide_id}" class="btn btn-buy w-100">Đặt ngay</a>
                        </div>
                    </div>
                </div>
            `;
        });
    })
    .catch(error => console.error('Lỗi:', error));
    // fetch tour
    fetch('../../server/gettour.php')
    .then(response => {
        return response.json();
    })
    .then(data => {
        let tourList = document.getElementById('tour-list');
        tourList.innerHTML = "";
        data.forEach(tour => {
            tourList.innerHTML += `
            <div class="col-md-6 col-lg-3">
                <div class="card tour-card d-flex flex-column">
                    <img src="${tour.image_url}" class="card-img-top tour-img" style="object-fit: cover; height: auto;">
                    <div class="card-body flex-grow-1 d-flex flex-column">
                        <h5 class="card-title">${tour.tour_name}</h5>
                      
                        <p class="text-danger fw-bold">Giá vé: ${Number(tour.price).toLocaleString('vi-VN')} VND</p>
                    </div>
                    <div class="p-3">
                        <a href="detail_tour.php?id=${tour.tour_id}" class="btn btn-buy w-100">Đặt vé</a>
                    </div>
                </div>
            </div>

        `;
        });
    })
    .catch(error => console.error('Lỗi:', error));
    document.addEventListener("DOMContentLoaded", function () {
        document.getElementById('bankDetails').style.display = 'block';
        document.getElementById('cashDetails').style.display = 'none';
    

        document.querySelectorAll('input[name="paymentMethod"]').forEach((input) => {
            input.addEventListener('change', (e) => {
                const method = e.target.value;
    
                document.getElementById('bankDetails').style.display = method === 'bankTransfer' ? 'block' : 'none';
                document.getElementById('cashDetails').style.display = method === 'cash' ? 'block' : 'none';
            });
        });
    });
    
    document.addEventListener('DOMContentLoaded', () => {
        const hotelList = document.getElementById('hotel-list');
        const citySelect = document.getElementById('citySelect');
        const searchButton = document.getElementById('searchButton');
        const destinationTitle = document.getElementById('destination-title');
        const destinations = [
            { id: 1, name: 'An Giang' },
            { id: 2, name: 'Cà Mau' },
            { id: 3, name: 'Bạc Liêu' },
            { id: 4, name: 'Cần Thơ' },
            { id: 5, name: 'Sóc Trăng' },
            { id: 6, name: 'Tiền Giang' }
        ];
    
        function getDestinationName(destinationId) {
            const destination = destinations.find(d => d.id === parseInt(destinationId, 10));
            return destination ;
        }

        function formatCurrency(amount) {
            return parseInt(amount).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' });
        }
    
        
        function displayHotels(data, destinationId = null) {
            if (destinationTitle) {
                const destinationName = getDestinationName(destinationId);
                destinationTitle.textContent = `Khách sạn tại ${destinationName}`;
            }

            hotelList.innerHTML = ""; 
            if (!data || data.length === 0) {
                hotelList.innerHTML = `
                    <div class="alert alert-info text-center" role="alert">
                        Không tìm thấy khách sạn
                    </div>
                `;
                return;
            }
            const hotelsHTML = data.map(hotel => `
                <div class="hotel-card d-flex mt-3">
                    <img src="${hotel.img_url || 'default-hotel-image.jpg'}" class="hotel-image" alt="${hotel.hotel_name}">
                    <div class="ms-3">
                        <h4>${hotel.hotel_name}</h4>
                        <p><i class="fa-solid fa-location-dot"></i> ${hotel.location || 'Chưa cập nhật'}</p>
                        <p class="price">Giá từ: ${formatCurrency(hotel.minprice || 0)}</p>
                        <a href="detail_hotels.php?hotel_id=${hotel.hotel_id}" class="btn btn-book">Đặt phòng</a>
                    </div>
                </div>
            `).join('');
    
            hotelList.innerHTML = hotelsHTML;
        }
        function filterHotels() {
            const city = citySelect.value;
            fetch(`../../server/get_ks.php?city=${city}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Lỗi kết nối');
                    }
                    return response.json();
                })
                .then(data => {
                    displayHotels(data, city);
                })
                .catch(error => {
                    console.error('Lỗi:', error);
                });
        }

        if (searchButton) {
            searchButton.addEventListener('click', filterHotels);
        }
        function loadInitialHotels() {
            fetch('../../server/get_ks.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Lỗi kết nối');
                    }
                    return response.json();
                })
                .then(data => {
                    displayHotels(data);
                })
                .catch(error => {
                    console.error('Lỗi:', error);
                    hotelList.innerHTML = `
                        <div class="alert alert-danger text-center" role="alert">
                            ${error.message || 'Đã xảy ra lỗi khi tải danh sách khách sạn'}
                        </div>
                    `;
                });
        }
    
        function populateDestinationDropdown() {
            if (citySelect) {
                destinations.forEach(destination => {
                    const option = document.createElement('option');
                    option.value = destination.id;
                    option.textContent = destination.name;
                    citySelect.appendChild(option);
                });
            }
        }
        populateDestinationDropdown();
        loadInitialHotels();
    });
    