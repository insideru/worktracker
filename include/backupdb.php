<?php
require_once 'db.php';

function backUpDBtoFile() {
    global $host;
    global $user;
    global $passwd;
    global $schema;

    try {

        // Get connection object and set the charset
        $conn = mysqli_connect($host, $user, $passwd, $schema);
        $conn->set_charset("utf8");

        // Get All Table Names From the Database
        $tables = array();
        $sql = "SHOW TABLES";
        $result = mysqli_query($conn, $sql);

        while ($row = mysqli_fetch_row($result)) {
            $tables[] = $row[0];
        }

        $sqlScript = "";

        foreach ($tables as $table) {
            // Prepare SQLscript for creating table structure
            $query = "SHOW CREATE TABLE $table";
            $result = mysqli_query($conn, $query);
            $row = mysqli_fetch_row($result);
            
            $sqlScript .= "\n\n" . $row[1] . ";\n\n";
            
            $query = "SELECT * FROM $table";
            $result = mysqli_query($conn, $query);
            
            $columnCount = mysqli_num_fields($result);
            
            // Prepare SQLscript for dumping data for each table
            for ($i = 0; $i < $columnCount; $i ++) {
                while ($row = mysqli_fetch_row($result)) {
                    $sqlScript .= "INSERT INTO $table VALUES(";
                    for ($j = 0; $j < $columnCount; $j ++) {
                        $row[$j] = $row[$j];
                        
                        if (isset($row[$j])) {
                            $sqlScript .= '"' . $row[$j] . '"';
                        } else {
                            $sqlScript .= '""';
                        }
                        if ($j < ($columnCount - 1)) {
                            $sqlScript .= ',';
                        }
                    }
                    $sqlScript .= ");\n";
                }
            }
            $sqlScript .= "\n";
        }

        // Save the SQL script to a backup file
        chdir('..');
        $backup_file  = getcwd() . "/backups/".date("Y-m-d")."-erp-backup.sql";
        $fileHandler = fopen($backup_file, 'w+');
        $number_of_lines = fwrite($fileHandler, $sqlScript);
        fclose($fileHandler);

        $gzfile = $backup_file.".gz";

        // Open the gz file (w9 is the highest compression)
        $fp = gzopen ($gzfile, 'w9');

        // Compress the file
        gzwrite ($fp, file_get_contents($backup_file));

        // Close the gz file and we're done
        gzclose($fp);

        //Delete uncompressed file
        unlink($backup_file);
        echo "Success!";
    }
    catch (exception $e) {
        //code to handle the exception
        echo "Failed: ".$e;
    }
}

$backup = backUpDBtoFile();
if (substr($backup, 0, 8) == "Success!")
{
    //ok, mail or not
    echo $backup;
} else {
    //not ok
    echo $backup;
}
?>