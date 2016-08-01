<?php
/**
 * Telegram Bot Sample
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Database support library. Don't change a thing here.
 */

 function db_default_error_logging($connection, $message = "Database error") {
    $errno = mysqli_errno($connection);
    $error = mysqli_error($connection);
    error_log("$message #$errno: $error");
 }

/**
 * Closes the existing connection to the database, if any.
 */
function db_close_connection() {
    if(isset($GLOBALS['db_connection'])) {
        $connection = $GLOBALS['db_connection'];

        mysqli_close($connection);
    }

    unset($GLOBALS['db_connection']);
}

/**
 * Creates or retrieves a connection to the database.
 * @param bool $quick True if the connection should be returned untested.
 * @return object A valid connection to the database.
 */
function db_open_connection($quick = false) {
    if(isset($GLOBALS['db_connection'])) {
        $connection = $GLOBALS['db_connection'];

        if(!$quick) {
            // Ping the connection just to be safe
            // This can be removed for performance since we usually have no
            // long-running scripts.
            if(!mysqli_ping($connection)) {
                error_log('Database connection already open but does not respond to ping');
                die();
            }
        }

        return $connection;
    }
    else {
        // Check configuration
        if(!DATABASE_USERNAME || !DATABASE_NAME) {
            error_log('Please configure the database connection in file config.php');
            die();
        }

        // Open up a new connection
        $connection = mysqli_connect(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);

        if(!$connection) {
            $errno = mysqli_connect_errno();
            $error = mysqli_connect_error();
            error_log("Failed to establish database connection. Error #$errno: $error");
            die();
        }

        // Store connection for later
        $GLOBALS['db_connection'] = $connection;

        // Register clean up function for termination
        register_shutdown_function('db_close_connection');

        return $connection;
    }
}

/**
 * Performs an "action query" (UPDATE, INSERT, REPLACE, or similar)
 * and returns the number of rows affected on success.
 * @param string $sql SQL query to perform.
 * @return bool | int Number of affected rows or false on failure.
 */
function db_perform_action($sql) {
    $connection = db_open_connection();

    if(!mysqli_real_query($connection, $sql)) {
        db_default_error_logging($connection, "Failed to perform query ($sql)");
        return false;
    }

    $affected_rows = mysqli_affected_rows($connection);
    if($affected_rows < 0) {
        db_default_error_logging($connection, "Failed to check for affected rows");
        return false;
    }

    return $affected_rows;
}

/**
 * Performs a "select query" which is expected to return one
 * single scalar value.
 * @param string $sql SQL query to perform.
 * @return mixed The single scalar value returned by the query,
 *               null if no value was returned, or false on failure.
 */
function db_scalar_query($sql) {
    $connection = db_open_connection();

    if(!mysqli_real_query($connection, $sql)) {
        db_default_error_logging($connection);
        return false;
    }

    $result = mysqli_store_result($connection);
    if($result === false) {
        db_default_error_logging($connection, "Failed to store query results");
        return false;
    }

    // Sanity checks
    if(mysqli_field_count($connection) !== 1) {
        mysqli_free_result($result);
        error_log("Query ($sql) generated results with multiple fields (non-scalar)");
        return false;
    }
    $num_rows = mysqli_num_rows($result);
    if($num_rows > 1) {
        mysqli_free_result($result);
        error_log("Query ($sql) generated more than one row of results (non-scalar)");
        return false;
    }
    else if($num_rows == 0) {
        mysqli_free_result($result);
        return null;
    }

    // Extract first row
    $row = mysqli_fetch_row($result);
    mysqli_free_result($result);

    //Error checking on results (just for the sake of it)
    if($row == null) {
        error_log("Failed to access first row of query results");
        return false;
    }
    if(count($row) < 1) {
        error_log("Results row is empty");
        return false;
    }

    // Extract and return first field
    return $row[0];
}

/**
 * Performs a "select query" and returns the complete
 * result data as a matrix. This function is best suited
 * for queries expected to return little data.
 * @param string $sql SQL query to perform.
 * @return bool | array The results table or false on failure.
 */
function db_table_query($sql) {
    $connection = db_open_connection();

    if(!mysqli_real_query($connection, $sql)) {
        db_default_error_logging($connection);
        return false;
    }

    $result = mysqli_store_result($connection);
    if($result === false) {
        db_default_error_logging($connection, "Failed to store query results");
        return false;
    }

    $matrix = [];
    while(($row = mysqli_fetch_row($result)) != null) {
        $matrix[] = $row;
    }

    mysqli_free_result($result);

    return $matrix;
}

/**
 * Performs a "select query" and returns one row of data as
 * an array. This function is inteded for queries expected to
 * return only one single row of data (e.g., queries with a
 * "LIMIT 1" SQL clause or with a "WHERE" clause you are sure
 * evaluates to true for only one row of the table).
 * This query will fail if more than one row of results is
 * generated.
 * @param string $sql SQL query to perform.
 * @return mixed The results row as an array, null if
 *               no results where generated, or false on failure.
 */
function db_row_query($sql) {
    $connection = db_open_connection();

    if(!mysqli_real_query($connection, $sql)) {
        db_default_error_logging($connection);
        return false;
    }

    $result = mysqli_store_result($connection);
    if($result === false) {
        db_default_error_logging($connection, "Failed to store query results");
        return false;
    }

    $num_rows = mysqli_num_rows($result);
    if($num_rows > 1) {
        mysqli_free_result($result);
        error_log("Query ($sql) generated more than one row of results");
        return false;
    }
    else if($num_rows == 0) {
        mysqli_free_result($result);
        return null;
    }

    $row = mysqli_fetch_row($result);
    mysqli_free_result($result);

    return $row;
}

/**
 * Escapes a string to be used as a value inside an SQL query.
 * @return string Escaped string.
 */
function db_escape($s) {
    return mysqli_real_escape_string(db_open_connection(true), (string)$s);
}

?>