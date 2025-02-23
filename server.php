<?php
header('Content-Type: application/json');

$fileBooks = 'books.json';
$fileBorrowings = 'borrowings.json';

// Charger les livres
function getBooks() {
    global $fileBooks;
    return file_exists($fileBooks) ? json_decode(file_get_contents($fileBooks), true) : [];
}

// Sauvegarder les livres
function saveBooks($books) {
    global $fileBooks;
    file_put_contents($fileBooks, json_encode($books, JSON_PRETTY_PRINT));
}

// Charger les emprunts
function getBorrowings() {
    global $fileBorrowings;
    return file_exists($fileBorrowings) ? json_decode(file_get_contents($fileBorrowings), true) : [];
}

// Sauvegarder les emprunts
function saveBorrowings($borrowings) {
    global $fileBorrowings;
    file_put_contents($fileBorrowings, json_encode($borrowings, JSON_PRETTY_PRINT));
}

// Ajouter un livre
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['action'] === 'addBook') {
    $book = json_decode(file_get_contents('php://input'), true);
    $books = getBooks();
    $book['id'] = uniqid();
    $book['available'] = true;
    $books[] = $book;
    saveBooks($books);
    echo json_encode(["success" => true, "message" => "Livre ajouté avec succès"]);
}

// Modifier un livre
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['action'] === 'updateBook') {
    $book = json_decode(file_get_contents('php://input'), true);
    $books = getBooks();
    foreach ($books as &$b) {
        if ($b['id'] === $book['id']) {
            $b['title'] = $book['title'];
            $b['author'] = $book['author'];
            break;
        }
    }
    saveBooks($books);
    echo json_encode(["success" => true, "message" => "Livre modifié avec succès"]);
}

// Supprimer un livre
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['action'] === 'deleteBook') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'];
    $books = array_values(array_filter(getBooks(), fn($book) => $book['id'] !== $id));
    saveBooks($books);
    echo json_encode(["success" => true, "message" => "Livre supprimé avec succès"]);
}

// Emprunter un livre
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['action'] === 'borrowBook') {
    $data = json_decode(file_get_contents('php://input'), true);
    $bookId = $data['bookId'];
    $user = $data['user'];

    $books = getBooks();
    $borrowings = getBorrowings();

    foreach ($books as &$book) {
        if ($book['id'] === $bookId && $book['available']) {
            $book['available'] = false; // Marquer comme emprunté
            $returnDate = date('Y-m-d', strtotime('+20 days'));
            $borrowings[] = [
                'user' => $user,
                'bookId' => $bookId,
                'borrowDate' => date('Y-m-d'),
                'returnDate' => $returnDate
            ];
            saveBooks($books);
            saveBorrowings($borrowings);
            echo json_encode([
                "success" => true,
                "message" => "Emprunt réussi. Date de retour : $returnDate",
                "returnDate" => $returnDate
            ]);
            return;
        }
    }
    echo json_encode(["success" => false, "message" => "Le livre est déjà emprunté ou n'existe pas"]);
}

// Retourner un livre
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['action'] === 'returnBook') {
    $data = json_decode(file_get_contents('php://input'), true);
    $bookId = $data['bookId'] ?? null;

    if (!$bookId) {
        echo json_encode(["success" => false, "message" => "ID du livre requis"]);
        exit;
    }

    $books = getBooks();
    $bookFound = false;

    // Ajouter un log pour vérifier l'ID et les livres disponibles
    error_log('ID recherché: ' . $bookId);
    error_log('Livres disponibles: ' . json_encode($books));

    // Parcours les livres pour trouver celui à retourner
    foreach ($books as &$book) {
        if ($book['id'] === $bookId) {
            // Marquer le livre comme disponible
            $book['available'] = true;
            // Supprimer l'utilisateur qui l'a emprunté
            $book['borrowedBy'] = null; 
            $bookFound = true;
            break;
        }
    }

    // Vérifier si le livre a bien été trouvé
    if (!$bookFound) {
        echo json_encode(["success" => false, "message" => "Livre introuvable"]);
        exit;
    }

    // Sauvegarder les modifications dans books.json
    saveBooks($books);

    echo json_encode(["success" => true, "message" => "Livre retourné avec succès et disponible"]);
}

// Récupérer tous les livres
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_GET['action'] === 'getBooks') {
    echo json_encode(getBooks());
}

// Récupérer les emprunts
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_GET['action'] === 'getBorrowings') {
    echo json_encode(getBorrowings());
}

?>
