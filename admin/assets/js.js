document.addEventListener('DOMContentLoaded', function() {
    const modals = {};
    document.querySelectorAll('.modal').forEach(function(modalElem) {
        modals[modalElem.id] = new bootstrap.Modal(modalElem);
    });

    let currentHotelId = null;
    let deleteItemId = null;
    let deleteItemType = null;
    const initButtonEvents = () => {
        const addHotelBtn = document.getElementById('addHotelBtn');
        if (addHotelBtn) {
            addHotelBtn.addEventListener('click', () => {
                if (modals['addHotelModal']) modals['addHotelModal'].show();
            });
        }

        const viewAllRoomsBtn = document.getElementById('viewAllRoomsBtn');
        if (viewAllRoomsBtn) {
            viewAllRoomsBtn.addEventListener('click', () => {
                if (modals['allRoomsModal']) modals['allRoomsModal'].show();
            });
        }

        document.querySelectorAll('.manage-rooms-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const hotelId = this.getAttribute('data-hotel-id');
                const hotelName = this.getAttribute('data-hotel-name');
                
                currentHotelId = hotelId;
                document.getElementById('hotelNameTitle').textContent = hotelName;
                
                if (modals['manageRoomsModal']) modals['manageRoomsModal'].show();
                loadRoomsForHotel(hotelId);
            });
        });

        const addRoomBtn = document.getElementById('addRoomBtn');
        if (addRoomBtn) {
            addRoomBtn.addEventListener('click', () => {
                document.getElementById('roomForm').reset();
                document.getElementById('roomId').value = '';
                document.getElementById('roomHotelId').value = currentHotelId;
                document.getElementById('roomFormModalLabel').textContent = 'Thêm Phòng Mới';
                document.getElementById('room-preview-image').classList.add('d-none');
                
                if (modals['roomFormModal']) modals['roomFormModal'].show();
            });
        }
    };

    const initRoomEvents = () => {
        document.addEventListener('click', function(e) {
            const editRoomBtn = e.target.closest('.edit-room-btn, .edit-room-btn-global');
            if (editRoomBtn) {
                const roomId = editRoomBtn.getAttribute('data-room-id');
                editRoom(roomId);
            }

            const deleteRoomBtn = e.target.closest('.delete-room-btn, .delete-room-btn-global');
            if (deleteRoomBtn) {
                const roomId = deleteRoomBtn.getAttribute('data-room-id');
                deleteRoom(roomId);
            }
        });
    };

    const initHotelEditEvents = () => {
        document.addEventListener('click', function(e) {
            const editHotelBtn = e.target.closest('.edit-hotel-btn, .edit-btn, #editHotelBtn');
            if (editHotelBtn) {
                const hotelId = editHotelBtn.getAttribute('data-hotel-id');
                editHotel(hotelId);
            }
        });
    };

    const initRoomDeleteEvents = () => {
        document.addEventListener('click', function(e) {
            const deleteRoomBtn = e.target.closest('.delete-room-btn, .delete-room-btn-global');
            if (deleteRoomBtn) {
                const roomId = deleteRoomBtn.getAttribute('data-room-id');

                const confirmModal = document.getElementById('confirmDeleteModal');
                if (confirmModal) {
                    const modalTitle = confirmModal.querySelector('.modal-title');
                    const modalBody = confirmModal.querySelector('.modal-body p'); 
                    if (modalTitle) modalTitle.textContent = 'Xác Nhận Xóa Phòng';
                    if (modalBody) modalBody.textContent = 'Bạn có chắc chắn muốn xóa phòng này?';
                    const confirmBtn = document.getElementById('confirmDeleteBtn');
                    if (confirmBtn) {
                        confirmBtn.textContent = 'Xóa Phòng';
                        confirmBtn.onclick = () => {
                            window.location.href = `hotel_manage.php?action=delete_room&id=${roomId}`;
                        };
                    }
                    const deleteModal = modals['confirmDeleteModal'];
                    if (deleteModal) deleteModal.show();
                }
            }
        });
    };



    const initImageTabEvents = () => {
        document.getElementById('upload-tab-btn').addEventListener('click', function() {
            document.getElementById('upload-tab').classList.add('show', 'active');
            document.getElementById('url-tab').classList.remove('show', 'active');
            this.classList.add('active');
            document.getElementById('url-tab-btn').classList.remove('active');
        });

        document.getElementById('url-tab-btn').addEventListener('click', function() {
            document.getElementById('url-tab').classList.add('show', 'active');
            document.getElementById('upload-tab').classList.remove('show', 'active');
            this.classList.add('active');
            document.getElementById('upload-tab-btn').classList.remove('active');
        });
    };

    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener('click', function(e) {
            const editBtn = e.target.closest('.edit-btn');
            if (editBtn) {
                const hotelId = editBtn.getAttribute('data-hotel-id');
                editHotel(hotelId);
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener('click', function(e) {
            const editBtn = e.target.closest('.edit-btn, #editHotelBtn');
            if (editBtn) {
                const hotelId = editBtn.getAttribute('data-hotel-id');
                if (hotelId) {
                    editHotel(hotelId);
                } else {
                    console.error('Không tìm thấy hotel ID');
                }
            }
        });
    });
    
    function loadRoomsForHotel(hotelId) {
        const tableBody = document.getElementById('roomsTableBody');
        tableBody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                </td>
            </tr>
        `;
        
        fetch('hotel_manage.php?action=get_rooms&hotel_id=' + hotelId)
            .then(response => response.json())
            .then(rooms => {
                let html = '';
                
                if (rooms.length === 0) {
                    html = `
                        <tr>
                            <td colspan="5" class="text-center">Không có phòng nào cho khách sạn này</td>
                        </tr>
                    `;
                } else {
                    rooms.forEach(room => {
                        html += `
                            <tr>
                                <td data-label="Loại Phòng">${room.room_type}</td>
                                <td data-label="Giá/Đêm">${formatCurrency(room.price_per_night)} đ</td>
                                <td data-label="Số Khách">${room.max_guests}</td>
                                <td data-label="Hình Ảnh">
                                    <img src="${room.img_url}" class="img-thumbnail" width="100" height="60" 
                                        onerror="this.src='assets/img/no-image.png'">
                                </td>
                                <td data-label="Thao Tác">
                                    <button class="btn btn-warning btn-sm edit-room-btn" data-room-id="${room.room_id}">
                                        <i class="fas fa-edit"></i> Sửa
                                    </button>
                                    <button class="btn btn-danger btn-sm delete-room-btn" data-room-id="${room.room_id}">
                                        <i class="fas fa-trash"></i> Xóa
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                }
                
                tableBody.innerHTML = html;
            })
            .catch(error => {
                console.error('Error loading rooms:', error);
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-danger">
                            <i class="fas fa-exclamation-circle"></i> Lỗi khi tải danh sách phòng
                        </td>
                    </tr>
                `;
            });
    }
    

    function editRoom(roomId) {
        fetch('hotel_manage.php?action=get_room&id=' + roomId)
            .then(response => response.json())
            .then(room => {
                if (room.error) {
                    alert('Lỗi: ' + room.error);
                    return;
                }
                document.getElementById('roomId').value = room.room_id;
                document.getElementById('roomHotelId').value = room.hotel_id;
                document.getElementById('room_type').value = room.room_type;
                document.getElementById('price_per_night').value = room.price_per_night;
                document.getElementById('max_guests').value = room.max_guests;
   
                const previewImg = document.getElementById('room-preview-image');
                if (room.img_url) {
                    previewImg.src = room.img_url;
                    previewImg.classList.remove('d-none');
                } else {
                    previewImg.classList.add('d-none');
                }
                
                document.getElementById('roomFormModalLabel').textContent = 'Sửa Thông Tin Phòng';
                
        
                if (modals['roomFormModal']) {
                    modals['roomFormModal'].show();
                }
                
                currentHotelId = room.hotel_id;
            })
            .catch(error => {
                console.error('Error loading room details:', error);
                alert('Có lỗi xảy ra khi tải thông tin phòng');
            });
    }

    function deleteRoom(roomId) {
        const confirmModal = document.getElementById('confirmDeleteModal');
        if (confirmModal) {
            const modalTitle = confirmModal.querySelector('.modal-title');
            const modalBody = confirmModal.querySelector('.modal-body p');
            
            if (modalTitle) modalTitle.textContent = 'Xác Nhận Xóa Phòng';
            if (modalBody) modalBody.textContent = 'Bạn có chắc chắn muốn xóa phòng này?';

            const confirmBtn = document.getElementById('confirmDeleteBtn');
            if (confirmBtn) {
                confirmBtn.textContent = 'Xóa Phòng';
                confirmBtn.onclick = () => {
                    window.location.href = `hotel_manage.php?action=delete_room&id=${roomId}`;
                };
            }

            const deleteModal = modals['confirmDeleteModal'];
            if (deleteModal) deleteModal.show();
        }
    }
    

    function filterRooms(searchTerm) {
        const rows = document.querySelectorAll('#allRoomsTableBody tr');
        
        rows.forEach(row => {
            const hotelName = row.querySelector('td[data-label="Khách Sạn"]')?.textContent.toLowerCase() || '';
            const roomType = row.querySelector('td[data-label="Loại Phòng"]')?.textContent.toLowerCase() || '';
            const price = row.querySelector('td[data-label="Giá/Đêm"]')?.textContent.toLowerCase() || '';
            
            if (hotelName.includes(searchTerm) || roomType.includes(searchTerm) || price.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
  
    

    function formatCurrency(value) {
        return new Intl.NumberFormat('vi-VN').format(value);
    }
    
    function confirmDeleteHotel(hotelId) {
        if (confirm('Bạn có chắc chắn muốn xóa khách sạn này? Tất cả phòng của khách sạn cũng sẽ bị xóa theo.')) {
            window.location.href = 'delete_hotel.php?hotel_id=' + hotelId;
        }
    }

    const initAllEvents = () => {
        initButtonEvents();
        initRoomEvents();
        initHotelEditEvents();
        initHotelDeleteEvents();
        initImagePreviewEvents();
        initImageTabEvents();
        initSearchAndSortEvents();
    };

    const autoCloseAlerts = () => {
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                bsAlert.close();
            });
        }, 5000);
    };
    initAllEvents();
    autoCloseAlerts();
});
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(e) {
        const editBtn = e.target.closest('.edit-btn');
        if (editBtn) {
            const hotelId = editBtn.getAttribute('data-hotel-id');

            fetch(`hotel_manage.php?action=get_hotel&id=${hotelId}`)
                .then(response => response.json())
                .then(hotel => {
        
                    document.querySelector('#editHotelForm #hotel_id').value = hotel.hotel_id;
                    document.querySelector('#editHotelForm #hotel_name').value = hotel.hotel_name;
                    document.querySelector('#editHotelForm #location').value = hotel.location;
                    document.querySelector('#editHotelForm #destination_id').value = hotel.destination_id;
                    document.querySelector('#editHotelForm #rating').value = hotel.rating;
                    document.querySelector('#editHotelForm #description').value = hotel.description;
                    
                    const previewImg = document.querySelector('#editHotelForm #preview-image');
                    if (hotel.img_url) {
            
                        let imageUrl = hotel.img_url;
                        if (imageUrl.startsWith('../img/')) {
                            imageUrl = imageUrl.replace('../img/', '../usr/img/');
                        }
                        previewImg.src = imageUrl;
                        previewImg.classList.remove('d-none');
                    } 
                    const editHotelModal = new bootstrap.Modal(document.getElementById('editHotelModal'));
                    editHotelModal.show();
                })
                .catch(error => {
                    console.error('Lỗi:', error);
                    alert('Không thể tải thông tin khách sạn');
                });
        }
    });


    
});

    document.getElementById('hotel_images').addEventListener('change', function (event) {
        const previewContainer = document.getElementById('preview-images');
        previewContainer.innerHTML = ''; 
        const files = event.target.files;

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const reader = new FileReader();
            reader.onload = function (e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.classList.add('img-thumbnail', 'me-2');
                img.style.maxHeight = '200px';
                previewContainer.appendChild(img);
            }
            reader.readAsDataURL(file);
        }
        previewContainer.classList.remove('d-none'); 
    });


function confirmDelete(hotelId, hotelName) {
    if (confirm(`Bạn có chắc chắn muốn xóa khách sạn "${hotelName}" không?`)) {
        window.location.href = `hotel_manage.php?delete_hotel=${hotelId}`;
    }
}
