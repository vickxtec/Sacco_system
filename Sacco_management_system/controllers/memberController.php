<?php
include '../config/db.php';
include '../models/Member.php';

if(isset($_POST['name'])){

    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    Member::create($conn,$name,$email,$phone);

    header("Location: ../views/members.php");
}
?>