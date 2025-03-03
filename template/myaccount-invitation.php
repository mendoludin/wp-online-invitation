<?php
if (is_user_logged_in()) {
    $user_id = get_current_user_id();
    echo '<h2>My Invitations</h2>';
    // Tambahkan logik untuk menampilkan order dan field ACF di sini
} else {
    echo 'Please log in to view your invitations.';
}
