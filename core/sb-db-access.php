// Function to connect to the remote database and delete a shortcut
function delete_shortcut($shortcut_id) {
    // Retrieve the database connection details from WordPress options
    $host = get_option('remote_db_host');
    $dbname = get_option('remote_db_name');
    $username = get_option('remote_db_user');
    $password = get_option('remote_db_password');

    try {
        // Create a new PDO instance for the remote database
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare and execute the DELETE statement
        $stmt = $pdo->prepare("DELETE FROM shortcuts WHERE id = :id");
        $stmt->bindParam(':id', $shortcut_id, PDO::PARAM_INT);
        $stmt->execute();

        echo "Shortcut deleted successfully.";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}