<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyBooky - Ajouter un livre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="book-add.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet"></head>
</head>

<body class="d-flex flex-column h-100">

<?php include_once('nav-bar.php'); 

if(empty($_SESSION)){

    header('location:index.php');

} else {

    $pdo = new \PDO('mysql:host=localhost;dbname=the_library_factory','root','');

    if($_SERVER['REQUEST_METHOD'] === 'POST'){

        $pdo = new \PDO('mysql:host=localhost;dbname=the_library_factory','root','');

        include_once('functions.php');

        /* Errors */

        if(empty($_POST['bookName']) || empty($_POST['bookPrice']) || (empty($_POST['authorLastname']) && empty($_POST['author_id']))){

            $errorMessage = 'Le nom du livre, son prix et le nom de l\'auteur sont obligatoires';

        }

        if((!empty($_POST['author_id'])) && (!empty($_POST['authorLastname']) || !empty($_POST['authorFirstname']))){

            $errorMessage = 'Choisissez : nouvel auteur ou auteur existant';

        }

        /* Verify author in database */
        
        $queryAuthorInDB = 'SELECT author.id, author.lastname FROM author JOIN book ON author.id=book.author_id WHERE author_id = \'' . testInput($_POST['author_id']) . '\'';
        $statementAuthorInDB = $pdo->query($queryAuthorInDB);
        $authorInDB = $statementAuthorInDB->fetchAll();

        var_dump($authorInDB); die;

        /* when choice different from select default choice value = string 0 */

        if(empty($authorInDB) || $_POST['author_id']==='0') {

            $errorMessage = 'L\'auteur n\'existe pas dans la base';

        }

        if(!is_int($_POST['author_id'])){

            $errorMessage = 'L\'auteur n\'existe pas dans la base';

        }

        /* verify existing book and author */

        $queryAuthorLastname = 'SELECT id, lastname FROM author WHERE lastname = \'' . testInput($_POST['authorLastname']) . '\' OR id = ' . testInput($_POST['author_id']) ;
        $statementAuthorLastname = $pdo->query($queryAuthorLastname);
        $authorLastnameMatch = $statementAuthorLastname->fetch();

        $queryTitleBook = 'SELECT name FROM book WHERE name = \'' . testInput($_POST['bookName']) . '\'';
        $statementTitleBook = $pdo->query($queryTitleBook);
        $titleBookMatch = $statementTitleBook->fetch();


        if($authorLastnameMatch && $titleBookMatch) {

            $errorMessage = 'Livre ou auteur déjà existant';

        }

        if(!empty($_POST['birthyear']) && !empty($_POST['deathyear']) && $_POST['birthyear'] >= $_POST['deathyear']){

            $errorMessage = "L'année de naissance doit être inférieure à l'année de mort.";
        
        }

        if($errorMessage === ''){

            if(!empty($_POST['authorLastname']) && (empty($_POST['author_id']))){

                /* insert informations with new author */

                $queryAuthor = 'INSERT INTO author (firstname, lastname, birthyear, deathyear) VALUES (:firstname, :lastname, :birthyear, :deathyear)';
        
                include_once('functions.php');
        
                $lastname = testInput($_POST['authorLastname']);
                $firstname = testInput($_POST['authorFirstname']);
                $birthyear = intval(testInput($_POST['birthyear']));
                $deathyear = intval(testInput($_POST['deathyear']));
                $bookName = testInput($_POST['bookName']);
                $bookPrice = floatval(testInput($_POST['bookPrice']));
                $bookSumup = testInputNotLowerCase($_POST['bookSumup']);
                $bookYear = intval(testInput($_POST['release_year']));
        
                $statementAuthor = $pdo->prepare($queryAuthor);
                $statementAuthor->bindValue(':firstname', $firstname, \PDO::PARAM_STR);
                $statementAuthor->bindValue(':lastname', $lastname, \PDO::PARAM_STR);
                $statementAuthor->bindValue(':birthyear', $birthyear, \PDO::PARAM_STR);
                $statementAuthor->bindValue(':deathyear', $deathyear, \PDO::PARAM_STR);
                $statementAuthor->execute();
        
                $queryIdUser = 'SELECT id FROM user WHERE id = ' . $_SESSION['id'];
                $statementIdUser = $pdo->query($queryIdUser);
                $userId = $statementIdUser->fetch();

                $queryIdAuthor = 'SELECT id FROM author ORDER BY id DESC';
                $statementIdAuthor = $pdo->query($queryIdAuthor);
                $authorId = $statementIdAuthor->fetch();

                /* insert new author_id in book */

                $queryBook = 'INSERT INTO book (name, author_id, user_id, price_book, sumup, release_year) VALUES(:bookname, :bookauthorid, :bookuserid, :bookprice, :sumup, :release_year)';
                $statementBook = $pdo->prepare($queryBook);
                $statementBook->bindValue(':bookname', $bookName, \PDO::PARAM_STR);
                $statementBook->bindValue(':bookauthorid', $authorId[0], \PDO::PARAM_STR);
                $statementBook->bindValue(':bookuserid', $userId[0], \PDO::PARAM_STR);
                $statementBook->bindValue(':bookprice', $bookPrice, \PDO::PARAM_STR);
                $statementBook->bindValue(':sumup', $bookSumup, \PDO::PARAM_STR);
                $statementBook->bindValue(':release_year', $bookYear, \PDO::PARAM_STR);

                $statementBook->execute();

                /* insert new book_id in likes */

                $queryIdBook = 'SELECT id FROM book ORDER BY id DESC';
                $statementIdBook = $pdo->query($queryIdBook);
                $bookId = $statementIdBook->fetch();

                /* initializing total of likes */

                $queryLikes = 'INSERT INTO likes (book_id, total) VALUES(:bookid, :total)';
                $statementLikes = $pdo->prepare($queryLikes);
                $statementLikes->bindValue(':bookid', $bookId[0], \PDO::PARAM_INT);
                $statementLikes->bindValue(':total', 0, \PDO::PARAM_INT);
                $statementLikes->execute();

                /* initializing total in likes_user */

                $queryLikesUser = 'INSERT INTO likes_user (likes_id, user_id, total) VALUES(:likesid, :userid, :total)';
                $statementLikesUser = $pdo->prepare($queryLikesUser);
                $statementLikesUser->bindValue(':likesid', $bookId[0], \PDO::PARAM_INT);
                $statementLikesUser->bindValue(':userid', $_SESSION['id'], \PDO::PARAM_INT);
                $statementLikesUser->bindValue(':total', 0, \PDO::PARAM_INT);
                $statementLikesUser->execute();

            }

            if((!empty($_POST['author_id'])) && (empty($_POST['authorLastname']))){
                
                /* insert informations with existing author */

                $queryBook = 'INSERT INTO book (name, author_id, user_id, price_book, sumup, release_year) VALUES(:bookname, :bookauthorid, :bookuserid, :bookprice, :sumup, :release_year)';
                
                include_once('functions.php');

                $queryIdUser = 'SELECT id FROM user WHERE id = ' . $_SESSION['id'];
                $statementIdUser = $pdo->query($queryIdUser);
                $userId = $statementIdUser->fetch();

                $bookName = testInput($_POST['bookName']);
                $bookPrice = floatval(testInput($_POST['bookPrice']));
                $bookSumup = testInputNotLowerCase($_POST['bookSumup']);
                $bookYear = intval(testInput($_POST['release_year']));

                $statementBook = $pdo->prepare($queryBook);
                $statementBook->bindValue(':bookname', $bookName, \PDO::PARAM_STR);
                $statementBook->bindValue(':bookauthorid', $_POST['author_id'], \PDO::PARAM_STR);
                $statementBook->bindValue(':bookuserid', $userId[0], \PDO::PARAM_STR);
                $statementBook->bindValue(':bookprice', $bookPrice, \PDO::PARAM_STR);
                $statementBook->bindValue(':sumup', $bookSumup, \PDO::PARAM_STR);
                $statementBook->bindValue(':release_year', $bookYear, \PDO::PARAM_STR);
                $statementBook->execute();

                /* insert new book_id in likes */ 

                $queryIdBook = 'SELECT id FROM book ORDER BY id DESC';
                $statementIdBook = $pdo->query($queryIdBook);
                $bookId = $statementIdBook->fetch();

                /* initializing total of likes */

                $queryLikes = 'INSERT INTO likes (book_id, total) VALUES(:bookid, :total)';
                $statementLikes = $pdo->prepare($queryLikes);
                $statementLikes->bindValue(':bookid', $bookId[0], \PDO::PARAM_INT);
                $statementLikes->bindValue(':total', 0, \PDO::PARAM_INT);
                $statementLikes->execute();

                /* initializing total in likes_user */

                $queryLikesUser = 'INSERT INTO likes_user (likes_id, user_id, total) VALUES(:likesid, :userid, :total)';
                $statementLikesUser = $pdo->prepare($queryLikesUser);
                $statementLikesUser->bindValue(':likesid', $bookId[0], \PDO::PARAM_INT);
                $statementLikesUser->bindValue(':userid', $_SESSION['id'], \PDO::PARAM_INT);
                $statementLikesUser->bindValue(':total', 0, \PDO::PARAM_INT);
                $statementLikesUser->execute();

            }

            header('location: book-personal-space.php');
            exit();
            
        }   

    }
?>

    <div class="container w-50 ">
        <div class="mt-5"></div>
            <h2 class="text-center">Vendez votre livre sur MyBooky</h2>
            <h5 class="text-center text-secondary">Ajoutez-le au catalogue</h5>
            <form action = "<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="mt-3">
                <div class="form-group mb-2">
                    <label for="bookName" class="text-secondary">Titre du livre (obligatoire)</label>
                    <input type="text" id="bookName" name="bookName" class="form-control" value="<?php if(!empty($_POST['bookName'])){echo $_POST['bookName'];}?>">
                </div>
                <div class="form-group mb-2">
                    <label for="bookPrice" class="text-secondary">Prix (obligatoire)</label>
                    <input type="number" step="0.01" id="bookPrice" name="bookPrice" class="form-control" value="<?php if(!empty($_POST['bookPrice'])){echo $_POST['bookPrice'];}?>">
                </div>
                <div class="form-group mb-2">
                    <label for="bookSumup" class="text-secondary">Résumé</label>
                    <textarea id="bookSumup" name="bookSumup" class="form-control" value="<?php if(!empty($_POST['bookPrice'])){echo $_POST['bookPrice'];}?>"></textarea>
                </div>
                <div class="form-group mb-2">
                    <label for="release_year" class="text-secondary">Date de parution</label>
                    <input type="number" id="release_year" name="release_year" class="form-control" value="<?php if(!empty($_POST['release_year'])){echo $_POST['release_year'];}?>">
                </div>
                <div class="form-group mb-2">
                    <label for="author_id"></label>
                    <select name="author_id" class="form-select select-add">
                        <option value="0">Auteur existant</option>
                        <?php
                            $queryAuthor = 'SELECT id, firstname, lastname FROM author ORDER BY lastname';
                            $statementAuthor = $pdo->query($queryAuthor);
                            $authors = $statementAuthor->fetchAll();
                                                    
                            foreach($authors as $author){ ?> 
                            <option value="<?php echo $author['id'] ;?>"><?php echo ucwords($author['lastname'] . ' ' . $author['firstname']) ;?></option> 
                         <?php } ?>
                    </select>
                </div>
                <p class="text-primary mt-2">ou</p>
                <div class="form-group mb-2">
                    <label for="authorLastname" class="text-secondary">Nom du nouvel auteur (obligatoire)</label>
                    <input type="text" id="authorLastname" name="authorLastname" class="form-control" value="<?php if(!empty($_POST['authorLastname'])){echo $_POST['authorLastname'];}?>">
                </div>
                <div class="form-group mb-2">
                    <label for="authorFirstname" class="text-secondary">Prénom du nouvel auteur</label>
                    <input type="text" id="authorFirstname" name="authorFirstname" class="form-control" value="<?php if(!empty($_POST['authorFirstname'])){echo $_POST['authorFirstname'];}?>">
                </div>
                <div class="author-years">
                    Année de naissance <input type="number" id="birthyear" name="birthyear" class="author-birth-year" value="<?php if(!empty($_POST['birthyear'])){echo $_POST['birthyear'];}?>"></input>
                    Année de mort <input type="number" id="deathyear" name="deathyear" class="author-death-year" value="<?php if(!empty($_POST['deathyear'])){echo $_POST['deathyear'];}?>"></input>
                </div>
                <div class="mt-2 errorMessage">
                    <?php if (!empty($errorMessage)) echo $errorMessage ; ?>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary mt-2">Ajouter</button>
                </div>
            </form>
        </div>
    </div>

    <?php include_once('footer.php'); ?>

</body>
</html>

<?php } ?>


<!-- old book-add -->

<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyBooky - Ajouter un livre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="book-add.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet"></head>
</head>

<body class="d-flex flex-column h-100">

<?php include_once('nav-bar.php'); 

if(empty($_SESSION)){
    header('location:index.php');
} else {


$pdo = new \PDO('mysql:host=localhost;dbname=the_library_factory','root','');


if($_SERVER['REQUEST_METHOD'] === 'POST'){
    
    if((!empty($_POST['author_id'])) && (!empty($_POST['authorLastname']) || !empty($_POST['authorFirstname']))){

        /* Verify choice between existing and new author in formular */

        $errorMessage = 'Choisissez : nouvel auteur ou auteur existant';

    } else {

        $errorMessage = '';

        include_once('functions.php');

        /* Verify author in database */
        $queryAuthorInDB = 'SELECT author.id FROM author JOIN book ON author.id=book.author_id WHERE author_id = \'' . testInput($_POST['author_id']) . '\'';
        $statementAuthorInDB = $pdo->query($queryAuthorInDB);
        $authorInDB = $statementAuthorInDB->fetchAll();


        if(empty($authorInDB) && $_POST['author_id']!=='0' /* when  different from select default choice value = string 0 */) {

        $errorMessage = 'L\'auteur n\'existe pas dans la base';

        } else {
                
            /* verify existing book and author */

            $queryAuthorLastname = 'SELECT lastname FROM author WHERE lastname = \'' . testInput($_POST['authorLastname']) . '\'';
            $statementAuthorLastname = $pdo->query($queryAuthorLastname);
            $authorLastnameMatch = $statementAuthorLastname->fetch();

            $queryTitleBook = 'SELECT name FROM book WHERE name = \'' . testInput($_POST['bookName']) . '\'';
            $statementTitleBook = $pdo->query($queryTitleBook);
            $titleBookMatch = $statementTitleBook->fetch();


            if($authorLastnameMatch || $titleBookMatch) {

                $errorMessage = 'Livre ou auteur déjà existant';

            } else {

                if(!empty($_POST['birthyear']) && !empty($_POST['deathyear']) && $_POST['birthyear'] >= $_POST['deathyear']){

                    $errorMessage = "L'année de naissance doit être inférieure à l'année de mort.";
                
                } else {

                if(!empty(($_POST['bookName'])) && (!empty($_POST['bookPrice'])) && (((!empty($_POST['authorLastname']))) || !empty($_POST['author_id']))){

                    $pdo = new \PDO('mysql:host=localhost;dbname=the_library_factory','root','');
                
                    if(!empty($_POST['authorLastname']) && (empty($_POST['author_id']))){
                        

                            /* insert informations with new author */

                            $queryAuthor = 'INSERT INTO author (firstname, lastname, birthyear, deathyear) VALUES (:firstname, :lastname, :birthyear, :deathyear)';
                    
                            include_once('functions.php');
                    
                            $lastname = testInput($_POST['authorLastname']);
                            $firstname = testInput($_POST['authorFirstname']);
                            $birthyear = intval(testInput($_POST['birthyear']));
                            $deathyear = intval(testInput($_POST['deathyear']));
                            $bookName = testInput($_POST['bookName']);
                            $bookPrice = floatval(testInput($_POST['bookPrice']));
                            $bookSumup = testInputNotLowerCase($_POST['bookSumup']);
                            $bookYear = intval(testInput($_POST['release_year']));
                    
                            $statementAuthor = $pdo->prepare($queryAuthor);
                            $statementAuthor->bindValue(':firstname', $firstname, \PDO::PARAM_STR);
                            $statementAuthor->bindValue(':lastname', $lastname, \PDO::PARAM_STR);
                            $statementAuthor->bindValue(':birthyear', $birthyear, \PDO::PARAM_STR);
                            $statementAuthor->bindValue(':deathyear', $deathyear, \PDO::PARAM_STR);
                            $statementAuthor->execute();
                    
                            $queryIdUser = 'SELECT id FROM user WHERE id = ' . $_SESSION['id'];
                            $statementIdUser = $pdo->query($queryIdUser);
                            $userId = $statementIdUser->fetch();

                            $queryIdAuthor = 'SELECT id FROM author ORDER BY id DESC';
                            $statementIdAuthor = $pdo->query($queryIdAuthor);
                            $authorId = $statementIdAuthor->fetch();

                            /* insert new author_id in book */

                            $queryBook = 'INSERT INTO book (name, author_id, user_id, price_book, sumup, release_year) VALUES(:bookname, :bookauthorid, :bookuserid, :bookprice, :sumup, :release_year)';
                            $statementBook = $pdo->prepare($queryBook);
                            $statementBook->bindValue(':bookname', $bookName, \PDO::PARAM_STR);
                            $statementBook->bindValue(':bookauthorid', $authorId[0], \PDO::PARAM_STR);
                            $statementBook->bindValue(':bookuserid', $userId[0], \PDO::PARAM_STR);
                            $statementBook->bindValue(':bookprice', $bookPrice, \PDO::PARAM_STR);
                            $statementBook->bindValue(':sumup', $bookSumup, \PDO::PARAM_STR);
                            $statementBook->bindValue(':release_year', $bookYear, \PDO::PARAM_STR);

                            $statementBook->execute();

                            /* insert new book_id in likes */

                            $queryIdBook = 'SELECT id FROM book ORDER BY id DESC';
                            $statementIdBook = $pdo->query($queryIdBook);
                            $bookId = $statementIdBook->fetch();

                            /* initializing total of likes */

                            $queryLikes = 'INSERT INTO likes (book_id, total) VALUES(:bookid, :total)';
                            $statementLikes = $pdo->prepare($queryLikes);
                            $statementLikes->bindValue(':bookid', $bookId[0], \PDO::PARAM_INT);
                            $statementLikes->bindValue(':total', 0, \PDO::PARAM_INT);
                            $statementLikes->execute();

                            /* initializing total in likes_user */

                            $queryLikesUser = 'INSERT INTO likes_user (likes_id, user_id, total) VALUES(:likesid, :userid, :total)';
                            $statementLikesUser = $pdo->prepare($queryLikesUser);
                            $statementLikesUser->bindValue(':likesid', $bookId[0], \PDO::PARAM_INT);
                            $statementLikesUser->bindValue(':userid', $_SESSION['id'], \PDO::PARAM_INT);
                            $statementLikesUser->bindValue(':total', 0, \PDO::PARAM_INT);
                            $statementLikesUser->execute();

                            header('location: book-personal-space.php');
                            exit();
                
                    }
                
                    if((!empty($_POST['author_id'])) && (empty($_POST['authorLastname']))){
                        
                            /* insert informations with existing author */

                            $queryBook = 'INSERT INTO book (name, author_id, user_id, price_book, sumup, release_year) VALUES(:bookname, :bookauthorid, :bookuserid, :bookprice, :sumup, :release_year)';
                            
                            include_once('functions.php');

                            $queryIdUser = 'SELECT id FROM user WHERE id = ' . $_SESSION['id'];
                            $statementIdUser = $pdo->query($queryIdUser);
                            $userId = $statementIdUser->fetch();

                            $bookName = testInput($_POST['bookName']);
                            $bookPrice = floatval(testInput($_POST['bookPrice']));
                            $bookSumup = testInputNotLowerCase($_POST['bookSumup']);
                            $bookYear = intval(testInput($_POST['release_year']));

                            $statementBook = $pdo->prepare($queryBook);
                            $statementBook->bindValue(':bookname', $bookName, \PDO::PARAM_STR);
                            $statementBook->bindValue(':bookauthorid', $_POST['author_id'], \PDO::PARAM_STR);
                            $statementBook->bindValue(':bookuserid', $userId[0], \PDO::PARAM_STR);
                            $statementBook->bindValue(':bookprice', $bookPrice, \PDO::PARAM_STR);
                            $statementBook->bindValue(':sumup', $bookSumup, \PDO::PARAM_STR);
                            $statementBook->bindValue(':release_year', $bookYear, \PDO::PARAM_STR);
                            $statementBook->execute();

                            /* insert new book_id in likes */ 

                            $queryIdBook = 'SELECT id FROM book ORDER BY id DESC';
                            $statementIdBook = $pdo->query($queryIdBook);
                            $bookId = $statementIdBook->fetch();

                            /* initializing total of likes */

                            $queryLikes = 'INSERT INTO likes (book_id, total) VALUES(:bookid, :total)';
                            $statementLikes = $pdo->prepare($queryLikes);
                            $statementLikes->bindValue(':bookid', $bookId[0], \PDO::PARAM_INT);
                            $statementLikes->bindValue(':total', 0, \PDO::PARAM_INT);
                            $statementLikes->execute();

                            /* initializing total in likes_user */

                            $queryLikesUser = 'INSERT INTO likes_user (likes_id, user_id, total) VALUES(:likesid, :userid, :total)';
                            $statementLikesUser = $pdo->prepare($queryLikesUser);
                            $statementLikesUser->bindValue(':likesid', $bookId[0], \PDO::PARAM_INT);
                            $statementLikesUser->bindValue(':userid', $_SESSION['id'], \PDO::PARAM_INT);
                            $statementLikesUser->bindValue(':total', 0, \PDO::PARAM_INT);
                            $statementLikesUser->execute();

                            header('location: book-personal-space.php');
                            exit();
                    }

                    header('location: book-personal-space.php');
                    exit();
                
                } else { 
                    
                    $errorMessage = 'Renseignez au moins le nom du livre, son prix et le nom de l\'auteur';
        }}}}

    }
}

?>
    <div class="container w-50 ">
        <div class="mt-5"></div>
            <h2 class="text-center">Vendez votre livre sur MyBooky</h2>
            <h5 class="text-center text-secondary">Ajoutez-le au catalogue</h5>
            <form action = "<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="mt-3">
                <div class="form-group mb-2">
                    <label for="bookName" class="text-secondary">Titre du livre (obligatoire)</label>
                    <input type="text" id="bookName" name="bookName" class="form-control" value="<?php if(!empty($_POST['bookName'])){echo $_POST['bookName'];}?>">
                </div>
                <div class="form-group mb-2">
                    <label for="bookPrice" class="text-secondary">Prix (obligatoire)</label>
                    <input type="number" step="0.01" id="bookPrice" name="bookPrice" class="form-control" value="<?php if(!empty($_POST['bookPrice'])){echo $_POST['bookPrice'];}?>">
                </div>
                <div class="form-group mb-2">
                    <label for="bookSumup" class="text-secondary">Résumé</label>
                    <textarea id="bookSumup" name="bookSumup" class="form-control" value="<?php if(!empty($_POST['bookPrice'])){echo $_POST['bookPrice'];}?>"></textarea>
                </div>
                <div class="form-group mb-2">
                    <label for="release_year" class="text-secondary">Date de parution</label>
                    <input type="number" id="release_year" name="release_year" class="form-control" value="<?php if(!empty($_POST['release_year'])){echo $_POST['release_year'];}?>">
                </div>
                <div class="form-group mb-2">
                    <label for="authorLastname"></label>
                    <select name="author_id" class="form-select select-add">
                        <option value="0">Auteur existant</option>
                        <?php
                            $queryAuthor = 'SELECT id, firstname, lastname FROM author ORDER BY lastname';
                            $statementAuthor = $pdo->query($queryAuthor);
                            $authors = $statementAuthor->fetchAll();
                                                    
                            foreach($authors as $author){ ?> 
                            <option value="<?php echo $author['id'] ;?>"><?php echo ucwords($author['lastname'] . ' ' . $author['firstname']) ;?></option> 
                         <?php } ?>
                    </select>
                </div>
                <p class="text-primary mt-2">ou</p>
                <div class="form-group mb-2">
                    <label for="authorLastname" class="text-secondary">Nom du nouvel auteur (obligatoire)</label>
                    <input type="text" id="authorLastname" name="authorLastname" class="form-control" value="<?php if(!empty($_POST['authorLastname'])){echo $_POST['authorLastname'];}?>">
                </div>
                <div class="form-group mb-2">
                    <label for="authorFirstname" class="text-secondary">Prénom du nouvel auteur</label>
                    <input type="text" id="authorFirstname" name="authorFirstname" class="form-control" value="<?php if(!empty($_POST['authorFirstname'])){echo $_POST['authorFirstname'];}?>">
                </div>
                <div class="author-years">
                    Année de naissance <input type="number" id="birthyear" name="birthyear" class="author-birth-year" value="<?php if(!empty($_POST['birthyear'])){echo $_POST['birthyear'];}?>"></input>
                    Année de mort <input type="number" id="deathyear" name="deathyear" class="author-death-year" value="<?php if(!empty($_POST['deathyear'])){echo $_POST['deathyear'];}?>"></input>
                </div>
                <div class="mt-2 errorMessage">
                    <?php if (!empty($errorMessage)) echo $errorMessage ; ?>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary mt-2">Ajouter</button>
                </div>
            </form>
        </div>
    </div>

    <?php include_once('footer.php'); ?>

</body>
</html>

<?php } ?>