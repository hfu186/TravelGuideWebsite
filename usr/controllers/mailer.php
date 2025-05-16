<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $conn;
    private $senderEmail;
    private $senderName;
    private $emailPassword;

    public function __construct($database_connection) {
        $this->conn = $database_connection;
        $this->senderEmail = 'huynhphuong186186@gmail.com';
        $this->senderName = 'Aloha Travel';
        $this->emailPassword = 'lovo erkb aisy mzsz'; 

        $this->requirePHPMailerFiles();
    }

    private function requirePHPMailerFiles() {
        $basePath = realpath(dirname(__FILE__));
        require_once($basePath . "/../../PHPMailer-master/PHPMailer-master/src/PHPMailer.php");
        require_once($basePath . "/../../PHPMailer-master/PHPMailer-master/src/SMTP.php");
        require_once($basePath . "/../../PHPMailer-master/PHPMailer-master/src/Exception.php");
    }
    
    private function createEmailBody($bookingDetails) {
        $serviceIcons = [
            'tour du lịch' => '🌍', 'khách sạn' => '🏨', 
            'Vé Sự Kiện' => '🎫', 'hướng dẫn viên' => '🧭'
        ];
        $serviceColors = [
            'tour' => '#2E8B57', 'hotel' => '#4169E1', 
            'ticket' => '#FF6347', 'guide' => '#8A2BE2'
        ];
    
        $icon = $serviceIcons[strtolower($bookingDetails['type'])] ;
        $color = $serviceColors[strtolower($bookingDetails['type'])] ?? '#007bff';
    
        $currentDate = date('d/m/Y H:i:s');
    
        $additionalDetails = $this->getAdditionalBookingDetails($bookingDetails);
    
        return $this->generateEmailHTML($bookingDetails, $icon, $color, $currentDate, $additionalDetails);
    }
    private function getServiceSpecificDetails($bookingDetails) {
        switch(strtolower($bookingDetails['type'])) {
            case 'tour':
                return "
                <tr>
                    <td><strong>Số Lượng:</strong></td>
                    <td>" . htmlspecialchars($bookingDetails['num_people'] ?? '1') . " người</td>
                </tr>";
            case 'hotel':
                return "
                <tr>
                    <td><strong>Số Phòng:</strong></td>
                    <td>" . htmlspecialchars($bookingDetails['room_quantity'] ?? '1') . " phòng</td>
                </tr>";
            case 'ticket':
                return "
                <tr>
                    <td><strong>Số Lượng Vé:</strong></td>
                    <td>" . htmlspecialchars($bookingDetails['ticket_quantity'] ?? '1') . " vé</td>
                </tr>";
            case 'guide':
                return "
                <tr>
                    <td><strong>Số Người:</strong></td>
                    <td>" . htmlspecialchars($bookingDetails['num_people'] ?? '1') . " người</td>
                </tr>";
            default:
                return '';
        }
    }
    
   
    private function generateEmailHTML($bookingDetails, $icon, $color, $currentDate, $additionalDetails) {

        $serviceDetails = $this->getServiceSpecificDetails($bookingDetails);
    
        return "
        <!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Xác Nhận Thanh Toán - Aloha Travel</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css'>
    <style>
        :root {
            --primary-color: #004503;
            --secondary-color: #17a2b8;
            --background-light: #f0f2f5;
            --text-color: #333;
            --border-radius: 8px;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #3f6a20, #010101);
            line-height: 1.6;
            color: var(--text-color);
            margin: 0;
            padding: 0;
        }

        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color:   rgb(255, 253, 253);
            border-radius: var(--border-radius);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .email-header {
            background: linear-gradient(90deg, #007bff, #17a2b8);
            color: white;
            text-align: center;
            padding: 25px;
        }

        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }

        .booking-details, .service-info {
            padding: 20px;
            margin: 15px;
            border-radius: var(--border-radius);
            background-color: #eef9ff;
            border-left: 4px solid var(--secondary-color);
        }

        .service-info {
            background-color: #e8fff8;
            border-left: 4px solid var(--primary-color);
        }

        .service-info table {
            width: 100%;
            border-collapse: collapse;
        }

        .service-info table tr {
            border-bottom: 1px solid #ddd;
        }

        .service-info table td {
            padding: 12px;
        }

        .qr-code-section {
            text-align: center;
            padding: 20px;
            background-color: #f8f9fa;
        }

        .qr-code-section img {
            max-width: 150px;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .footer {
            background-color: #f1f3f5;
            padding: 20px;
            text-align: center;
            font-size: 14px;
            color: #6c757d;
        }

        .total-price {
            font-weight: bold;
            color: var(--primary-color);
            font-size: 1.2em;
        }

        @media (max-width: 600px) {
            .email-container {
                width: 100%;
                margin: 0;
                border-radius: 0;
            }
        }
    </style>
</head>
        <body>
            <div class='email-container'>
                <div class='email-header'>
                    <h1>{$icon} XÁC NHẬN THANH TOÁN</h1>
                    <p>Cảm ơn bạn đã tin tưởng Aloha Travel</p>
                </div>
    
                <div class='booking-details'>
                    <h3>Thông Tin Khách Hàng</h3>
                    <p><strong>Tên:</strong> " . htmlspecialchars($bookingDetails['customer_name']) . "</p>
                    <p><strong>Mã Đặt Chỗ:</strong> #" . htmlspecialchars($bookingDetails['booking_id']) . "</p>
                </div>
    
                <div class='service-info'>
                    <h4>Chi Tiết Dịch Vụ</h4>
                    <table class='table'>
                        <tbody>
                            <tr>
                                <td><strong>Loại Dịch Vụ:</strong></td>
                                <td>" . strtoupper(htmlspecialchars($bookingDetails['type'])) . "</td>
                            </tr>
                            {$additionalDetails}
                            {$serviceDetails}
                            <tr>
                                <td><strong>Tổng Thanh Toán:</strong></td>
                                <td class='text-end fw-bold'>" . 
                                    number_format($bookingDetails['total_price'], 0, ',', '.') . " VNĐ
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
    
                <div class='text-center p-3'>
                    <img src='" . $this->generateQRCode($bookingDetails) . "' 
                         alt='Mã QR Xác Nhận' style='max-width:150px;'>
                    <p class='text-muted'>Quét mã QR để xác nhận đặt chỗ</p>
                </div>
                 <div class='text-center p-3'>
                    <p class='text-muted'>Nếu xảy ra sai sót trong quá trình lấy vé hãy đưa mã này cho nhân viên</p>
                </div>
    
                <div class='text-center p-3 bg-light'>
                    <p>© " . date('Y') . " Aloha Travel. Bản quyền được bảo lưu.</p>
                    <p>Hotline: 0942035835 | Email: support@alohatravel.com</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    
    
    private function generateQRCode($bookingDetails) {

        $qrData = json_encode([
            'booking_id' => $bookingDetails['booking_id'],
            'type' => $bookingDetails['type'],
            'total_price' => $bookingDetails['total_price']
        ]);
    
        $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . 
                     urlencode($qrData);
        
        return $qrCodeUrl;
    }
    
    private function getAdditionalBookingDetails($bookingDetails) {
        switch(strtolower($bookingDetails['type'])) {
            case 'tour':
                return "
                <tr>
                    <td><strong>Điểm Đến:</strong></td>
                    <td>" . htmlspecialchars(mb_substr($bookingDetails['destination'] ?? 'Chưa xác định', 0, 30)) . "</td>
                </tr>
                <tr>
                    <td><strong>Ngày Khởi Hành:</strong></td>
                    <td>" . (isset($bookingDetails['start_date']) ? 
                        date('d/m/Y', strtotime($bookingDetails['start_date'])) : 'Chưa xác định') . "</td>
                </tr>";
            case 'hotel':
                return "
                <tr>
                    <td><strong>Khách Sạn:</strong></td>
                    <td>" . htmlspecialchars(mb_substr($bookingDetails['hotel_name'] ?? 'Chưa xác định', 0, 30)) . "</td>
                </tr>
                <tr>
                    <td><strong>Ngày Lưu Trú:</strong></td>
                    <td>" . 
                        (isset($bookingDetails['check_in']) ? date('d/m/Y', strtotime($bookingDetails['check_in'])) : 'Chưa xác định') . 
                        " - " . 
                        (isset($bookingDetails['check_out']) ? date('d/m/Y', strtotime($bookingDetails['check_out'])) : 'Chưa xác định') . 
                    "</td>
                </tr>";
            case 'ticket':
                return "
                <tr>
                    <td><strong>Sự Kiện:</strong></td>
                    <td>" . htmlspecialchars(mb_substr($bookingDetails['event_name'] ?? 'Chưa xác định', 0, 30)) . "</td>
                </tr>
                <tr>
                    <td><strong>Ngày Diễn Ra:</strong></td>
                    <td>" . (isset($bookingDetails['event_date']) ? 
                        date('d/m/Y H:i', strtotime($bookingDetails['event_date'])) : 'Chưa xác định') . "</td>
                </tr>";
            case 'guide':
                return "
                <tr>
                    <td><strong>Địa Điểm:</strong></td>
                    <td>" . htmlspecialchars(mb_substr($bookingDetails['location'] ?? 'Chưa xác định', 0, 30)) . "</td>
                </tr>
                <tr>
                    <td><strong>Ngày Thuê:</strong></td>
                    <td>" . (isset($bookingDetails['guide_date']) ? 
                        date('d/m/Y', strtotime($bookingDetails['guide_date'])) : 'Chưa xác định') . "</td>
                </tr>";
            default:
                return '';
        }
    }
    

    private function sendEmail($recipient, $subject, $body) {
        $mail = new PHPMailer(true);

        try {
            $mail->CharSet = 'UTF-8';
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->senderEmail;
            $mail->Password   = $this->emailPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            $mail->setFrom($this->senderEmail, $this->senderName);
            $mail->addAddress($recipient);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);
            $this->logEmailAttempt($recipient, $subject);
            return $mail->send();

        } catch (Exception $e) {
        
            return false;
        }
    }

    private function logEmailAttempt($recipient, $subject) {
        $logFile = '../../logs/email_attempts_' . date('Y-m-d') . '.log';
        $logMessage = date('[Y-m-d H:i:s]') . " Attempting to send email\n";
        $logMessage .= "Recipient: $recipient\n";
        $logMessage .= "Subject: $subject\n\n";
     
    }


    public function sendPaymentConfirmationEmail($recipient, $bookingDetails) {
        $subject = 'Xác Nhận Thanh Toán - ' . $bookingDetails['type'];
        $body = $this->createEmailBody($bookingDetails);
        return $this->sendEmail($recipient, $subject, $body);
    }

    public function processPaymentEmail($booking_id, $booking_type) {
        try {
            $booking_details = $this->fetchBookingDetails($booking_id, $booking_type);
            
            if (!$booking_details) {
                throw new Exception("Không tìm thấy thông tin đặt chỗ");
            }
            
            $recipient_email = $booking_details['email'] ?? '';
            
       
            $emailBookingDetails = array_merge($booking_details, [
                'booking_id' => $booking_id,
                'type' => $this->getBookingType($booking_type),
                'total_price' => $booking_details['total_price'] ?? 0,
                'customer_name' => $booking_details['customer_name'] ?? 'Quý Khách'
            ]);
    
            return $this->sendPaymentConfirmationEmail($recipient_email, $emailBookingDetails);
    
        } catch (Exception $e) {
         
            return false;
        }
    }
    
    private function fetchBookingDetails($booking_id, $booking_type) {
        $query_map = [
            'hotel' => "
                SELECT 
                    *,
                    u.email,
                    u.usr_name AS customer_name,
                    h.hotel_name,
                    r.room_type,
                    h.location
                FROM hotel_bookings hb
                JOIN users u ON hb.usr_id = u.usr_id
                JOIN hotels h ON hb.hotel_id = h.hotel_id
                JOIN rooms r ON hb.room_id = r.room_id
                WHERE hb.booking_id = :booking_id
            ",
            'tour' => "
                     SELECT 
                *,
                u.email,
                u.usr_name AS customer_name,
                t.tour_name,
                d.name AS destination,
                t.days AS tour_duration,
              
                td.departure_date, 
                td.end_date,   
                tb.num_people
            FROM tour_bookings tb
            JOIN users u ON tb.usr_id = u.usr_id
            JOIN tours t ON tb.tour_id = t.tour_id
            JOIN tour_dates td ON tb.tour_date_id = td.tour_date_id  
            JOIN destinations d ON t.destination_id = d.destination_id
            WHERE tb.booking_id = :booking_id
            ",
            'ticket' => "
                SELECT 
                    *,
                    u.email,
                    u.usr_name AS customer_name,
                    t.ticketname AS event_name,
                    t.location AS venue
                FROM ticket_bookings tkb
                JOIN users u ON tkb.usr_id = u.usr_id
                JOIN tickets t ON tkb.ticket_id = t.ticket_id
                WHERE tkb.booking_id = :booking_id
            ",
            'guide' => "
                SELECT 
                    *,
                    u.email,
                    u.usr_name AS customer_name,
                    tg.name AS guide_name,
                    tg.language,
                    tg.experience AS experience_years,
                    d.name AS location
                FROM guide_bookings gb
                JOIN users u ON gb.usr_id = u.usr_id
                JOIN tour_guides tg ON gb.guide_id = tg.guide_id
                LEFT JOIN destinations d ON tg.destination_id = d.destination_id
                WHERE gb.booking_id = :booking_id
            "
        ];
    
        if (!isset($query_map[$booking_type])) {
            throw new Exception("Loại đặt chỗ không hợp lệ");
        }
    
        try {
            $stmt = $this->conn->prepare($query_map[$booking_type]);
            $stmt->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $booking_details = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$booking_details) {
                throw new Exception("Không tìm thấy thông tin đặt chỗ");
            }
            
            return $booking_details;
        } catch (PDOException $e) {
           
            throw $e;
        }
    }
    
    private function enrichBookingDetails($booking_details, $booking_type) {

        switch(strtolower($booking_type)) {
            case 'tour':
                return array_merge($booking_details, [
                    'booking_id' => $booking_details['booking_id'],
                    'type' => 'Tour Du Lịch',
                    'total_price' => $booking_details['total_price'] ?? 0,
                    'customer_name' => $booking_details['customer_name'] ?? 'Quý Khách',
                    'destination' => $booking_details['destination'] ?? 'Chưa xác định',
                    'start_date' => $booking_details['start_date'] ?? null,
                    'tour_name' => $booking_details['tour_name'] ?? 'Chưa xác định',
                    'tour_duration' => $booking_details['tour_duration'] ?? 'Chưa xác định',
                    'num_people' => $booking_details['num_people'] ?? 1,
                    'booking_code' => $booking_details['booking_code'] ?? 'Chưa có mã'
                ]);
    
            case 'hotel':
                return array_merge($booking_details, [
                    'booking_id' => $booking_details['booking_id'],
                    'type' => 'Đặt Phòng Khách Sạn',
                    'total_price' => $booking_details['total_price'] ?? 0,
                    'customer_name' => $booking_details['customer_name'] ?? 'Quý Khách',
                    'hotel_name' => $booking_details['hotel_name'] ?? 'Chưa xác định',
                    'check_in' => $booking_details['check_in'] ?? null,
                    'check_out' => $booking_details['check_out'] ?? null,
                    'room_type' => $booking_details['room_type'] ?? 'Chưa xác định',
                    'room_quantity' => $booking_details['quantity'] ?? 1,
                    'location' => $booking_details['location'] ?? 'Chưa xác định'
                ]);
    
            case 'ticket':
                return array_merge($booking_details, [
                    'booking_id' => $booking_details['booking_id'],
                    'type' => 'Vé Sự Kiện',
                    'total_price' => $booking_details['total_price'] ?? 0,
                    'customer_name' => $booking_details['customer_name'] ?? 'Quý Khách',
                    'event_name' => $booking_details['event_name'] ?? 'Chưa xác định',
                    'venue' => $booking_details['venue'] ?? 'Chưa xác định',
                    'ticket_quantity' => $booking_details['quantity'] ?? 1,
                    'booking_date' => $booking_details['booking_date'] ?? null
                ]);
    
            case 'guide':
                return array_merge($booking_details, [
                    'booking_id' => $booking_details['booking_id'],
                    'type' => 'Hướng Dẫn Viên',
                    'total_price' => $booking_details['total_price'] ?? 0,
                    'customer_name' => $booking_details['customer_name'] ?? 'Quý Khách',
                    'location' => $booking_details['location'] ?? 'Chưa xác định',
                    'guide_date' => $booking_details['booking_date'] ?? null,
                    'guide_name' => $booking_details['guide_name'] ?? 'Chưa xác định',
                    'language' => $booking_details['language'] ?? 'Chưa xác định',
                    'days' => $booking_details['days'] ?? 1,
                    'experience_years' => $booking_details['experience_years'] ?? 'Chưa cung cấp'
                ]);
    
            default:
                return $booking_details;
        }
    }
    
    private function getBookingType($booking_type) {
        $type_map = [
            'hotel' => 'Đặt Phòng Khách Sạn',
            'tour' => 'Tour Du Lịch',
            'ticket' => 'Vé Sự Kiện',
            'guide' => 'Hướng Dẫn Viên'
        ];

        return $type_map[$booking_type] ?? 'Dịch Vụ';
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'send_email') {
    require_once('../../server/connectdb.php');
        $booking_id = filter_input(INPUT_GET, 'booking_id', FILTER_VALIDATE_INT);
        $booking_type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
        $recipient_email = filter_input(INPUT_GET, 'email', FILTER_VALIDATE_EMAIL);
        $errors = [];
    
        if (!$booking_id) {
            $errors[] = 'Mã đặt chỗ không hợp lệ';
        }
    
        if (!$booking_type || !in_array($booking_type, ['hotel', 'tour', 'ticket', 'guide'])) {
            $errors[] = 'Loại dịch vụ không hợp lệ';
        }
    
        if (!$recipient_email) {
            $errors[] = 'Địa chỉ email không hợp lệ';
        }
    
        if (!empty($errors)) {
            echo json_encode([
                'status' => 'error', 
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $errors
            ]);
            exit();
        }
    
        try {
            $emailService = new EmailService($conn);
            $result = $emailService->processPaymentEmail($booking_id, $booking_type);
            if ($result) {
                error_log("Email gửi thành công cho: $recipient_email");
                
                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Gửi email xác nhận thanh toán thành công',
                    'email' => $recipient_email
                ]);
                header('location: ../../index.php');
                exit();
            } else {

                error_log("Gửi email thất bại cho: $recipient_email");
                
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Gửi email xác nhận thanh toán thất bại',
                    'email' => $recipient_email
                ]);
                header('location: ../../index.php');
                exit();
            }
        } catch (Exception $e) {
    
            error_log("Lỗi không mong muốn khi gửi email: " . $e->getMessage());
            
            echo json_encode([
                'status' => 'error', 
                'message' => 'Đã xảy ra lỗi không mong muốn',
                'error_details' => $e->getMessage()
            ]);
            header('location: ../../index.php');
            exit();
        }
    
    }
    

?>