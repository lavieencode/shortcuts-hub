// Function to connect to the remote database and delete a shortcut
function delete_shortcut($shortcut_id) {
    try {
        $db = SB_DB_Manager::get_instance();
        
        // Log the deletion attempt
        sh_debug_log('Shortcut Deletion', array(
            'message' => 'Attempting to delete shortcut',
            'source' => array(
                'file' => __FILE__,
                'line' => __LINE__,
                'function' => __FUNCTION__
            ),
            'data' => array(
                'shortcut_id' => $shortcut_id
            ),
            'debug' => true
        ));

        $result = $db->execute_query(function($pdo) use ($shortcut_id) {
            $stmt = $pdo->prepare("DELETE FROM shortcuts WHERE id = :id");
            $stmt->bindParam(':id', $shortcut_id, PDO::PARAM_INT);
            $stmt->execute();
            return true;
        });

        // DEBUG: Log successful deletion
        sh_debug_log('Shortcut Deletion', array(
            'message' => 'Shortcut deleted successfully',
            'source' => array(
                'file' => __FILE__,
                'line' => __LINE__,
                'function' => __FUNCTION__
            ),
            'data' => array(
                'shortcut_id' => $shortcut_id
            ),
            'debug' => true
        ));

        return "Shortcut deleted successfully.";
    } catch (PDOException $e) {
        // DEBUG: Log deletion error
        sh_debug_log('Shortcut Deletion Error', array(
            'message' => 'Failed to delete shortcut',
            'source' => array(
                'file' => __FILE__,
                'line' => __LINE__,
                'function' => __FUNCTION__
            ),
            'data' => array(
                'shortcut_id' => $shortcut_id,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ),
            'debug' => true
        ));
        
        throw new Exception("Error deleting shortcut: " . $e->getMessage());
    }
}