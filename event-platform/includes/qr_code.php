<?php
/**
 * QR Code Helper
 * Generates QR codes using Google Charts API (simple, no dependencies)
 */

class QRCodeGenerator {
    
    /**
     * Generate QR code URL using Google Charts API
     * @param string $data - Data to encode in QR code
     * @param int $size - Size of QR code in pixels (default 200)
     * @return string - URL to QR code image
     */
    public static function generateURL($data, $size = 200) {
        $encoded_data = urlencode($data);
        return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encoded_data}";
    }
    
    /**
     * Generate QR code for ticket verification
     * @param string $ticket_code - Unique ticket code
     * @param string $base_url - Base URL of the application
     * @return string - QR code image URL
     */
    public static function forTicket($ticket_code, $base_url) {
        $verification_url = $base_url . "/pages/tickets/verify.php?code=" . $ticket_code;
        return self::generateURL($verification_url, 250);
    }
    
    /**
     * Generate QR code for event details
     * @param int $event_id - Event ID
     * @param string $base_url - Base URL of the application
     * @return string - QR code image URL
     */
    public static function forEvent($event_id, $base_url) {
        $event_url = $base_url . "/pages/event_details.php?id=" . $event_id;
        return self::generateURL($event_url, 300);
    }
    
    /**
     * Generate inline QR code as base64 data URI
     * @param string $data - Data to encode
     * @param int $size - Size in pixels
     * @return string - Base64 data URI
     */
    public static function generateInline($data, $size = 200) {
        $url = self::generateURL($data, $size);
        $image_data = @file_get_contents($url);
        
        if ($image_data) {
            return 'data:image/png;base64,' . base64_encode($image_data);
        }
        
        return $url; // Fallback to URL if fetch fails
    }
}
?>
