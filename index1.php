<?php
$dataFile = 'users.json';

// Function to load users from the JSON file
function loadUsers() {
    global $dataFile;
    if (file_exists($dataFile)) {
        $jsonData = file_get_contents($dataFile);
        return json_decode($jsonData, true) ?? [];
    }
    return [];
}

// Function to save users to the JSON file
function saveUsers($users) {
    global $dataFile;
    file_put_contents($dataFile, json_encode($users));
}

// Handling form submission to add or edit a user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['name']) && isset($_POST['email']) && isset($_POST['place'])) {
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $place = htmlspecialchars($_POST['place']);
    $id = isset($_POST['id']) ? $_POST['id'] : null;

    $users = loadUsers();

    if ($id) {
        // Edit user if ID is provided
        foreach ($users as &$user) {
            if ($user['id'] === $id) {
                $user['name'] = $name;
                $user['email'] = $email;
                $user['place'] = $place;
                break;
            }
        }
    } else {
        // Add a new user if no ID is provided
        $newUser = [
            'id' => uniqid(),
            'name' => $name,
            'email' => $email,
            'place' => $place
        ];
        $users[] = $newUser;
    }

    saveUsers($users);
}

// AJAX delete operation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteId'])) {
    $idToDelete = $_POST['deleteId'];
    $users = loadUsers();

    // Filter out the user with the matching ID
    $updatedUsers = array_filter($users, function ($user) use ($idToDelete) {
        return $user['id'] !== $idToDelete;
    });

    // Save the updated users back to the JSON file
    if (count($updatedUsers) !== count($users)) {
        saveUsers($updatedUsers);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found.']);
    }
    exit;
}

// Load users to display
$users = loadUsers();
$usersData = "<h3>Users List:</h3><div class='users-list'>";
foreach ($users as $user) {
    $usersData .= "<div class='user' data-id='{$user['id']}'>
                      <strong>Name:</strong> {$user['name']}<br>
                      <strong>Email:</strong> {$user['email']}<br>
                      <strong>Place:</strong> {$user['place']}<br>
                      <button class='edit-button' onclick='editUser(\"{$user['id']}\", \"{$user['name']}\", \"{$user['email']}\", \"{$user['place']}\")'>Edit</button>
                      <button class='delete-button' onclick='deleteUser(\"{$user['id']}\")'>Delete</button>
                      <br><br>
                   </div>";
}
$usersData .= "</div>";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic PHP Form with CRUD</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="form-container">
    <form method="POST" id="userForm">
        <input type="hidden" name="id" id="userId">
        Name: <input type="text" name="name" id="userName" required><br>
        Place: <input type="text" name="place" id="userPlace" required><br>
        Email: <input type="email" name="email" id="userEmail" required><br>
        <input type="submit" value="Submit" id="submitButton">
    </form>
    
    <button id="toggleButton">Show Data</button>

    <div id="userData">
        <?php echo $usersData; ?>
    </div>
</div>

<script>
// Function to populate form for editing
function editUser(id, name, email, place) {
    document.getElementById('userId').value = id;
    document.getElementById('userName').value = name;
    document.getElementById('userEmail').value = email;
    document.getElementById('userPlace').value = place;
    document.getElementById('submitButton').value = 'Update';
}

// AJAX function to delete user
function deleteUser(id) {
    if (confirm('Are you sure you want to delete this user?')) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'index.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onload = function () {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.status === 'success') {
                        // Remove the deleted user's element without reloading
                        const userDiv = document.querySelector(`.user[data-id="${id}"]`);
                        if (userDiv) {
                            userDiv.remove();
                        }
                    } else {
                        alert('Error deleting user: ' + (response.message || 'Unknown error.'));
                    }
                } catch (e) {
                    console.error('Error parsing JSON response:', e);
                }
            } else {
                alert('Error communicating with server.');
            }
        };

        xhr.onerror = function () {
            alert('Network error occurred.');
        };

        xhr.send('deleteId=' + encodeURIComponent(id));
    }
}

// Show/Hide User Data
document.getElementById("toggleButton").addEventListener("click", function() {
    var userDataDiv = document.getElementById("userData");
    if (userDataDiv.style.display === "none" || userDataDiv.style.display === "") {
        userDataDiv.style.display = "block";
        this.textContent = "Hide Data";
    } else {
        userDataDiv.style.display = "none";
        this.textContent = "Show Data";
    }
});
</script>

</body>
</html>
