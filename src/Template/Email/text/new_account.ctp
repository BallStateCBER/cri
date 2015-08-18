<?= $user['User']['name'] ?>,

Your new account on the Community Readiness Initiative website (<?= $home_url ?>) has been created.

You can now log in to the CRI website at <?= $login_url ?> using the following information:
- Email: <?= $user['User']['email']."\n" ?>
- Password: <?= $user['User']['unhashed_password']."\n" ?>

Once logged in, you can change your password. If you have any questions, please email cri@bsu.edu.


Ball State Center for Business and Economic Research
cber@bsu.edu
www.bsu.edu/cber
765-285-5926