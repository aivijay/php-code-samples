<?php
  #
  # dbchecker.php - Check the status of a connection from a Web server to a 
  # database
  #
  # export RUNTIME_ENV with production, development or qa for switching
  # between runtime environments
  # eg. export RUNTIME_ENV=development for development environment access
  #
  # author: vijay
  # Date: May 3 2011
  # $Id:$
  #

  #
  # DbChecker - Class to handle DB checking
  #
  class DbChecker {
    private $dbh = NULL;

    #
    # Connect to DB
    #
    # return true on successful connection else false
    #
    function dbConnect() {
      #
      # DB arguments
      # defaults to (development environment)
      #
      $host     = "192.168.1.1";
      $port     = "2003";
      $dbname   = "test";
      $username = "root";
      $password = "admin";

      #
      # Check for RUNTIME_ENV (productoin, qa or development)
      #
      if (getenv('RUNTIME_ENV') != null) {
        $env = getenv('RUNTIME_ENV');
      } else {
        $env = 'development'; // default environment
      }
    
      #
      # Set host and port as appropriate to the environment
      #
      switch ($env) {
        case "qa":
          $host = "192.168.1.2";
          $port = "2002";
          break;
        case "production":
          $host = "192.168.1.3";
          $port = "2001";
          break;
      }

      #
      # Connect to database
      #
      try {
        # 
        # Make DB connection
        #
        $this->dbh = new PDO("mysql:host=$host;port=$port;dbname=test", "root", "admin");

        #
        # Enable exceptions
        #
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return true;
      } catch (Exception $e) {
        #
        # log unable to connect to database
        #
        $this->log("CRITICAL: Unable to connect to database. Message: " . $e->getMessage());

        #
        # Send email and notify sysadmins
        #
        $this->notifyEmail();

        return false;
      }
    }

    #
    # maintain logs
    #
    function log($message) {
      error_log(date('Y-m-d h:i:s') . " - " . $message . "\n", 3, "dbchecker.log");
    }

    # 
    # Notify email
    #
    function notifyEmail() {
      $email      = "someemail@exampleemail.org";
      $name       = "DbChecker"; //senders name
      $email      = "someemail@exampleeamil.org"; //senders e-mail adress
      $recipient  = "recipientemail@example2.com"; //recipient
      $mail_body  = "CRITICAL: Database connection issues"; //mail body
      $subject    = "CRITICAL: Database connection issues"; //subject
      $header     = "From: ". $name . " <" . $email . ">\r\n"; //optional headerfields

      mail($recipient, $subject, $mail_body, $header); //mail command :)
    }

    #
    # Fetch current user count and update STATUS table
    #
    function userStatus () {
      try {
        $stmt = $this->dbh->query("SELECT count(*) as count FROM USERS");
    
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
          $userCount = $row['count'];
          $timeStamp = date('Y-m-d h:i:s');

          # Insert timestamped record into STATUS table
          $this->dbh->exec("INSERT INTO STATUS VALUES('$timeStamp', $userCount)");
        }
      } catch (Exception $e) {
        $dbChecker->log("DB exception: " . $e->getMessage());
      }
    }

    #
    # Fetch latest status data
    #
    function latestStatus() {
      try {
        $stmt = $this->dbh->query("SELECT * FROM STATUS ORDER BY dateTime desc limit 1");
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
          return $row;
        }
      } catch (Exception $e) {
        $dbChecker->log("DB exception: " . $e->getMessage());
      }
    }

    #
    # Disconnect the database handle
    #
    function dbDisconnect() {
      $this->dbh = NULL;
    }
     
    #
    # Fetch the latest error message for display
    #
    function latestError() {
      return `tail -1 dbchecker.log`; 
    }
  }

  #
  # Check for proper database connection, update user status and disconnect
  #
  $dbChecker = new DbChecker;

  if ($dbChecker->dbConnect() == true) {
    $dbChecker->userStatus();

    #
    # fetch latest status data
    #
    $latestStatusData = $dbChecker->latestStatus();
    $dateTime = $latestStatusData['dateTime'];
    $numUsers = $latestStatusData['numUsers'];

    $dbChecker->dbDisconnect();
  }

?>

<html>
<head>
  <title>DbChecker latest error</title>
  <META HTTP-EQUIV="REFRESH" CONTENT="5">
  <style>
    .header {
      font-family: arial;
      font-weight: bold;
    }
    .data {
      font-family: arial;
      font-weight: normal;
    }   
  </style>
</head>
<body style="font-family: arial;">
  <?php
    if (isset($_GET['results']) and $_GET['results'] == "true") {
  ?>
  <table border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td colspan="2" align="left" class="header">
        Latest status from DbChecker
      </td>
    </tr>
    <tr>
      <td width="30%">
        Date
      </td>
      <td width="70%">
        <?php
           echo $dateTime;
        ?>
      </td>
    </tr>
    <tr>
      <td>
        Number of Users
      </td>
      <td>
        <?php 
          echo $numUsers;
        ?>    
      </td>
    </tr>
    <tr>
      <td height="20">
      </td>
    </tr>
    <tr>
      <td colspan="2" align="left" class="header">
        Latest Error from DbChecker
      </td>
    </tr>
    <tr>
      <td colspan="2">
        <?php
          echo $dbChecker->latestError();
        ?>
      </td>
    </tr> 
  </table>
  <?php
    }
  ?>
</body>
</html>
