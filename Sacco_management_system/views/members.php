<?php
include '../config/db.php';
include '../models/Member.php';

$members = Member::all($conn);
?>

<?php include 'header.php'; ?>

<h2>Register Member</h2>

<form action="../controllers/memberController.php" method="POST">

<input type="text" name="name" placeholder="Name" required>
<input type="email" name="email" placeholder="Email">
<input type="text" name="phone" placeholder="Phone">

<button type="submit">Add Member</button>

</form>

<h3>Members List</h3>

<table border="1">
<tr>
<th>ID</th>
<th>Name</th>
<th>Email</th>
<th>Phone</th>
</tr>

<?php while($row=$members->fetch_assoc()){ ?>

<tr>
<td><?php echo $row['id']; ?></td>
<td><?php echo $row['name']; ?></td>
<td><?php echo $row['email']; ?></td>
<td><?php echo $row['phone']; ?></td>
</tr>

<?php } ?>

</table>

<?php include 'footer.php'; ?>