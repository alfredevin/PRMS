<?php
include 'config.php'; // Adjust path if needed

$msg = "";
$icon = "";
$reservation_id = 0;
$room_name = "Room Stay";
$room_img = "uploads/default_room.jpg";

// 1. DECODE ID & FETCH ROOM DETAILS
if (isset($_GET['ref'])) {
    $reservation_id = intval(base64_decode($_GET['ref']));

    $room_query = "SELECT r.room_name, r.image 
                   FROM reservation_tbl res
                   JOIN rooms_tbl r ON res.room_id = r.room_id
                   WHERE res.reservation_id = '$reservation_id'";

    $room_result = mysqli_query($conn, $room_query);

    if ($row = mysqli_fetch_assoc($room_result)) {
        $room_name = htmlspecialchars($row['room_name']);
        if (!empty($row['image'])) {
            $room_img = "user/admin/" . htmlspecialchars($row['image']);
        }
    }
}

// 2. HANDLE SUBMIT
if (isset($_POST['submit_rating'])) {
    $res_id = intval($_POST['res_id']);
    $rating = intval($_POST['rating']);
    $feedback = mysqli_real_escape_string($conn, $_POST['feedback']);

    $check = mysqli_query($conn, "SELECT * FROM reviews_tbl WHERE reservation_id = '$res_id'");

    if (mysqli_num_rows($check) > 0) {
        $msg = "You have already submitted a review for this stay.";
        $icon = "info";
    } else {
        $get_room = mysqli_query($conn, "SELECT room_id FROM reservation_tbl WHERE reservation_id = '$res_id'");
        $room_row = mysqli_fetch_assoc($get_room);
        $actual_room_id = $room_row['room_id'];

        $sql = "INSERT INTO reviews_tbl (reservation_id, room_id, rating, feedback) 
                VALUES ('$res_id', '$actual_room_id', '$rating', '$feedback')";

        if (mysqli_query($conn, $sql)) {
            $msg = "Thank you! Your feedback for " . $room_name . " has been submitted.";
            $icon = "success";
        } else {
            $msg = "Error submitting feedback: " . mysqli_error($conn);
            $icon = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Your Stay: <?= $room_name ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
            padding: 20px;
        }

        .rating-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            padding: 40px 30px;
            max-width: 450px;
            width: 100%;
            text-align: center;
            position: relative;
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease;
        }

        .rating-card:hover {
            transform: translateY(-5px);
        }

        /* Dynamic Reaction GIF */
        .reaction-gif {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            margin: -90px auto 10px;
            background: #fff;
            border: 5px solid #fff;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.4s ease-in-out;
        }

        .room-title {
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        /* Star Rating Animation */
        .rating-box {
            display: flex;
            justify-content: center;
            flex-direction: row-reverse;
            gap: 10px;
            margin: 20px 0;
        }

        .rating-box input {
            display: none;
        }

        .rating-box label {
            cursor: pointer;
            font-size: 35px;
            color: #e0e0e0;
            transition: all 0.3s ease;
        }

        .rating-box input:checked~label,
        .rating-box label:hover,
        .rating-box label:hover~label {
            color: #ffc107;
            transform: scale(1.2);
            text-shadow: 0 0 10px rgba(255, 193, 7, 0.5);
        }

        /* Feedback Textarea */
        textarea {
            border-radius: 15px !important;
            background: #f8f9fa;
            border: 2px solid transparent !important;
            padding: 15px;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        textarea:focus {
            background: #fff;
            border-color: #667eea !important;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.15) !important;
        }

        /* Submit Button */
        .btn-submit {
            background: linear-gradient(to right, #667eea, #764ba2);
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            letter-spacing: 1px;
            width: 100%;
            color: white;
            margin-top: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(118, 75, 162, 0.3);
        }

        .rating-text {
            font-weight: 600;
            color: #667eea;
            height: 25px;
            /* prevent layout shift */
            margin-bottom: 10px;
        }
    </style>
</head>

<body>

    <div class="rating-card">
        <img src="<?= $room_img ?>" id="reactionImg" class="reaction-gif" alt="Reaction">

        <h3 class="room-title">How was <?= $room_name ?>?</h3>
        <p class="text-muted small">We'd love to hear your thoughts!</p>

        <form method="POST">
            <input type="hidden" name="res_id" value="<?= $reservation_id ?>">

            <div class="rating-text" id="ratingText">Select a star rating</div>

            <div class="rating-box">
                <input type="radio" id="star5" name="rating" value="5" onclick="changeReaction(5)"><label for="star5" title="Amazing"><i class="fas fa-star"></i></label>
                <input type="radio" id="star4" name="rating" value="4" onclick="changeReaction(4)"><label for="star4" title="Good"><i class="fas fa-star"></i></label>
                <input type="radio" id="star3" name="rating" value="3" onclick="changeReaction(3)"><label for="star3" title="Average"><i class="fas fa-star"></i></label>
                <input type="radio" id="star2" name="rating" value="2" onclick="changeReaction(2)"><label for="star2" title="Bad"><i class="fas fa-star"></i></label>
                <input type="radio" id="star1" name="rating" value="1" onclick="changeReaction(1)"><label for="star1" title="Terrible"><i class="fas fa-star"></i></label>
            </div>

            <div class="mb-3">
                <textarea name="feedback" class="form-control" rows="4" placeholder="Tell us more about your experience..."></textarea>
            </div>

            <button type="submit" name="submit_rating" class="btn btn-submit">
                Submit Review <i class="fas fa-paper-plane ms-2"></i>
            </button>
        </form>

        <div class="mt-4">
            <a href="./website/" class="text-muted small text-decoration-none hover-link">
                <i class="fas fa-home me-1"></i> Back to Home
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // --- Interactive Reaction Logic ---
        const reactionImg = document.getElementById('reactionImg');
        const ratingText = document.getElementById('ratingText');
        const defaultImg = "<?= $room_img ?>";

        // GIFs URLs (You can replace these with local files or other URLs)
        const reactions = {
            1: {
                img: "https://media.tenor.com/ECAWlIq3tXIAAAAj/sad-crying.gif",
                text: "Oh no! We're sorry."
            },
            2: {
                img: "https://media.tenor.com/2l1y1d4p0goAAAAj/sad-face.gif",
                text: "We can do better."
            },
            3: {
                img: "https://media.tenor.com/bX6v5a7gXQAAAAAj/thinking-face.gif",
                text: "It was okay."
            },
            4: {
                img: "https://media.tenor.com/kZq7q3q3tXIAAAAj/thumbs-up.gif",
                text: "Glad you liked it!"
            },
            5: {
                img: "https://media.tenor.com/Hw7f-4l0zgEAAAAj/check-mark-success.gif",
                text: "Awesome! Thank you!"
            }
        };

        function changeReaction(rating) {
            // Change Image with animation
            reactionImg.style.transform = "scale(0.8) rotate(-10deg)";
            setTimeout(() => {
                reactionImg.src = reactions[rating].img;
                ratingText.textContent = reactions[rating].text;
                ratingText.style.color = (rating <= 2) ? '#e74a3b' : (rating == 3 ? '#f6c23e' : '#1cc88a');
                reactionImg.style.transform = "scale(1.1) rotate(5deg)";
                setTimeout(() => reactionImg.style.transform = "scale(1) rotate(0deg)", 200);
            }, 200);
        }

        // Handle PHP Messages
        <?php if ($msg != "") { ?>
            Swal.fire({
                title: "<?= ($icon == 'success') ? 'Thank You!' : 'Notice' ?>",
                text: "<?= $msg ?>",
                icon: "<?= $icon ?>",
                confirmButtonColor: '#667eea',
                confirmButtonText: 'Okay',
                backdrop: `rgba(0,0,123,0.4) url("https://media.tenor.com/Hw7f-4l0zgEAAAAj/check-mark-success.gif") left top no-repeat`
            }).then(() => {
                if ("<?= $icon ?>" == "success") {
                    window.location.href = "./website/";
                }
            });
        <?php } ?>
    </script>

</body>

</html>