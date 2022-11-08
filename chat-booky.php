<?php 

$title="ChatBooky";

require('head.php');

?>

<?php 

if(empty($_SESSION)){

  header('location:index.php');

} else {

?>

<?php 

$queryAllUsers = 'SELECT id, firstname, lastname FROM user WHERE id != ' . $_SESSION['id'] . ' ORDER BY lastname ';
$statementAllUsers = $pdo->query($queryAllUsers);
$allUsers = $statementAllUsers->fetchAll(); 

?>

  <div class="container w-50 text-center">
    <h2 class="text-center mt-5">ChatBooky</h2>
  </div>

  <section class="h-100 h-custom" style="background-color: #eee;">
    <div class="container py-5 h-100">
      <div class="row d-flex justify-content-center align-items-center h-100">
          <div class="card mb-10">
            <div class="card-body p-4">
              <div class="notif-invitations">
                <h3>Commencez une nouvelle conversation</h3>
              </div>
              <div class="">
                <form method="post" action="chat-notification-sent.php" class="chat-booky-form">
                  <select name="select-chat-user-id" id="select-user-chat" class="form-select chat-select-user">
                    <option value="">Choisissez utilisateur</option>
                    <?php 
                    /* all users */
                        foreach($allUsers as $user){ ?>
                          <option value="<?php echo $user['id'] ?> " 
                                <?php 
                                if(!empty($_POST['select-chat-user-id']) && $_POST['select-chat-user-id'] == $user['id']){
                                  echo "selected";
                                } ?> >
                                <?php echo ucwords($user['firstname'] . ' ' . $user['lastname']) ;?></option>
                    <?php } ?>
                  </select>
                  <button type="submit" class="btn btn-primary">Envoyez une invitation</button>
                </form>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-body p-4">
              <div class="notif-invitations">
                <h3><a href="chat-messages.php" class="chat-option-button">Messages</a></h3> 
                <p class="chat-count-invitations">
                <?php
                /* all notifications */
                $queryNotifMessages = 'SELECT * FROM message WHERE user_destination_id = ' . $_SESSION['id'] . ' AND  seen_by_user_destination=false';
                $statementNotifMessages = $pdo->query($queryNotifMessages);
                $notifMessages = $statementNotifMessages->fetchAll();
                echo count($notifMessages);?>
                </p>   
              </div>              
            </div>
          </div>
          <div class="card">
            <div class="card-body p-4">
              <div class="notif-invitations">
                <h3><a href="chat-invitations.php" class="chat-option-button">Invitations reçues</a></h3> 
                <p class="chat-count-invitations">
                <?php
                /* all notifications */
                $queryNotif = 'SELECT * FROM notification WHERE user_id = ' . $_SESSION['id'] . ' AND accepted_by_source_user_id=true AND accepted_by_user_id=false AND type = \'invitation\'';
                $statementNotif = $pdo->query($queryNotif);
                $notifs = $statementNotif->fetchAll();
                echo count($notifs);?>
                </p>
              </div>
                                
            </div>
          </div>
      </div>
    </div>
  </section>
    
<?php } ?>

<?php include_once('footer.php'); ?>

</body>
</html>

