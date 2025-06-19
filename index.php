    <?php
    session_start();

    // Inicializa o jogo se necessário
    if (!isset($_SESSION['game_started']) || isset($_GET['reset'])) {
        $difficulty = isset($_GET['difficulty']) ? $_GET['difficulty'] : 'medium';
        initializeGame($difficulty);
    }

    function initializeGame($difficulty) {
        // Define o número de pares baseado na dificuldade
        switch ($difficulty) {
            case 'easy':
                $pairs = 6;
                break;
            case 'hard':
                $pairs = 12;
                break;
            case 'medium':
            default:
                $pairs = 8;
                break;
        }
        
        // Lista de imagens disponíveis (com nomes em português)
        $images = [
            ['file' => 'construcao.jpg', 'name' => 'Construção'],
            ['file' => 'servidao.jpg', 'name' => 'Servidão'],
            ['file' => 'agricultura.jpg', 'name' => 'Agricultura'],
            ['file' => 'comercio.webp', 'name' => 'Comércio'],
            ['file' => 'manual.jpg', 'name' => 'Manualidade'],
            ['file' => 'exploracao.jpg', 'name' => 'Exploração'],
            ['file' => 'obediencia.jpg', 'name' => 'Obediência'],
            ['file' => 'dominacao.webp', 'name' => 'Dominação'],
            ['file' => 'producao.jpg', 'name' => 'Produção'],
            ['file' => 'escravidao.webp', 'name' => 'Escravidão'],
            ['file' => 'desvalorizacao.jpg', 'name' => 'Desvalorização'],
            ['file' => 'divisao.webp', 'name' => 'Divisão']
        ];
        
        // Seleciona aleatoriamente os pares necessários
        shuffle($images);
        $selectedImages = array_slice($images, 0, $pairs);
        
        // Cria o array de cartas (duplicando cada imagem para formar pares)
        $cards = [];
        foreach ($selectedImages as $image) {
            $cards[] = $image;
            $cards[] = $image;
        }
        
        // Embaralha as cartas
        shuffle($cards);
        
        // Salva o estado do jogo na sessão
        $_SESSION['cards'] = $cards;
        $_SESSION['flipped'] = [];
        $_SESSION['matched'] = [];
        $_SESSION['attempts'] = 0;
        $_SESSION['game_started'] = true;
        $_SESSION['start_time'] = time();
        $_SESSION['difficulty'] = $difficulty;
    }

    // Processa a jogada quando uma carta é clicada
    if (isset($_POST['action']) && $_POST['action'] == 'flip') {
        $cardIndex = $_POST['card'];
        
        // Verifica se a carta já foi virada ou encontrada
        if (!in_array($cardIndex, $_SESSION['flipped']) && !in_array($cardIndex, $_SESSION['matched'])) {
            // Adiciona a carta às cartas viradas
            $_SESSION['flipped'][] = $cardIndex;
            
            // Se duas cartas foram viradas, verifica se são iguais
            if (count($_SESSION['flipped']) == 2) {
                $card1 = $_SESSION['flipped'][0];
                $card2 = $_SESSION['flipped'][1];
                
                $_SESSION['attempts']++;
                
                if ($_SESSION['cards'][$card1]['file'] == $_SESSION['cards'][$card2]['file']) {
                    // Cartas iguais - adiciona às cartas encontradas
                    $_SESSION['matched'][] = $card1;
                    $_SESSION['matched'][] = $card2;
                }
                
                // Limpa as cartas viradas para a próxima jogada
                $_SESSION['flipped'] = [];
            }
        }
        
        // Retorna o estado atual do jogo em JSON
        header('Content-Type: application/json');
        echo json_encode([
            'cards' => $_SESSION['cards'],
            'flipped' => $_SESSION['flipped'],
            'matched' => $_SESSION['matched'],
            'attempts' => $_SESSION['attempts'],
            'gameComplete' => (count($_SESSION['matched']) == count($_SESSION['cards']))
        ]);
        exit;
    }

    // Reinicia o timer a cada atualização de página (exceto em POST flip ou reset)
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['reset'])) {
        $_SESSION['start_time'] = time();
    }

    // Calcula o tempo decorrido
    $elapsedTime = isset($_SESSION['start_time']) ? time() - $_SESSION['start_time'] : 0;
    $minutes = floor($elapsedTime / 60);
    $seconds = $elapsedTime % 60;
    $formattedTime = sprintf('%02d:%02d', $minutes, $seconds);

    // Determina o número de colunas com base na dificuldade
    $columns = 4;
    if ($_SESSION['difficulty'] == 'easy') {
        $columns = 3;
    } elseif ($_SESSION['difficulty'] == 'hard') {
        $columns = 6;
    }
    ?>

    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Jogo da Memória</title>
        <style>
            body {
                font-family: 'Arial', sans-serif;
                background-color: #c9d41b;
                margin: 0;
                padding: 20px;
                display: flex;
                flex-direction: column;
                align-items: center;
                min-height: 100vh;
            }
            
            .game-container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
            }
            
            .timer {
                background-color: #5a9a94;
                color: white;
                padding: 8px 20px;
                border-radius: 20px;
                font-size: 18px;
                margin-bottom: 20px;
                text-align: center;
                width: 100px;
            }
            
            .game-board {
                display: grid;
                grid-template-columns: repeat(<?php echo $columns; ?>, 1fr);
                gap: 15px;
                margin-bottom: 20px;
                max-width: 1200px;
            }
            
            .card {
                aspect-ratio: 1/1;
                perspective: 1000px;
                cursor: pointer;
                min-width: 150px;
                min-height: 150px;
            }
            
            .card-inner {
                position: relative;
                width: 100%;
                height: 100%;
                transition: transform 0.6s;
                transform-style: preserve-3d;
                border-radius: 10px;
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            }
            
            .card.flipped .card-inner {
                transform: rotateY(180deg);
            }
            
            .card-front, .card-back {
                position: absolute;
                width: 100%;
                height: 100%;
                backface-visibility: hidden;
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .card-front {
                background: linear-gradient(135deg, #2c7873, #52b6ac);
                background-image: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(0,0,0,0.1) 10px, rgba(0,0,0,0.1) 20px);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 2.5rem;
                font-weight: bold;
                color: #fff;
                text-shadow: 2px 2px 8px rgba(44, 120, 115, 0.7), 0 0 10px #0008;
                letter-spacing: 2px;
                border-radius: 10px;
                opacity: 0.95;
                box-shadow: 0 2px 8px rgba(44,120,115,0.15);
            }
            
            .card-back {
                background-color: #fff;
                transform: rotateY(180deg);
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 5px;
            }
            
            .card-back img {
                max-width: 90%;
                max-height: 70%;
                object-fit: contain;
            }
            
            .card-back .card-name {
                margin-top: 5px;
                font-size: 14px;
                text-align: center;
            }
            
            .controls {
                margin-top: 20px;
                text-align: center;
            }
            
            .difficulty-btn {
                background-color: #5a9a94;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 20px;
                cursor: pointer;
                font-size: 16px;
                transition: background-color 0.3s;
            }
            
            .difficulty-btn:hover {
                background-color: #3d7a75;
            }
            
            .dropdown {
                position: relative;
                display: inline-block;
            }
            
            .dropdown-content {
                display: none;
                position: absolute;
                background-color: #f9f9f9;
                min-width: 160px;
                box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
                z-index: 1;
                border-radius: 5px;
                overflow: hidden;
            }
            
            .dropdown-content a {
                color: black;
                padding: 12px 16px;
                text-decoration: none;
                display: block;
            }
            
            .dropdown-content a:hover {
                background-color: #f1f1f1;
            }
            
            .dropdown:hover .dropdown-content {
                display: block;
            }
            
            .info {
                position: absolute;
                bottom: 10px;
                right: 10px;
                font-size: 12px;
                color: #555;
            }
            
            @media (max-width: 768px) {
                .game-board {
                    grid-template-columns: repeat(<?php echo min($columns, 4); ?>, 1fr);
                }
            }
            
            @media (max-width: 480px) {
                .game-board {
                    grid-template-columns: repeat(<?php echo min($columns, 3); ?>, 1fr);
                }
            }
        </style>
    </head>
    <body>
        <h2>Jogo da Memoria - Trabalho na Antiguidade Clássica<h2>        
        <div class="game-container">
            <div class="timer" id="timer"><?php echo $formattedTime; ?></div>
            
            <div class="game-board" id="gameBoard">
                <?php foreach ($_SESSION['cards'] as $index => $card): ?>
                    <div class="card <?php echo in_array($index, $_SESSION['matched']) ? 'flipped' : ''; ?>" data-index="<?php echo $index; ?>">
                        <div class="card-inner">
                            <div class="card-front"><?php echo $index + 1; ?></div>
                            <div class="card-back">
                                <img src="images/<?php echo $card['file']; ?>" alt="<?php echo $card['name']; ?>">
                                <div class="card-name"><?php echo $card['name']; ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="controls">
                <div class="dropdown">
                    <button class="difficulty-btn">Trocar dificuldade</button>
                    <div class="dropdown-content">
                        <a href="?difficulty=easy&reset=1">Fácil</a>
                        <a href="?difficulty=medium&reset=1">Médio</a>
                        <a href="?difficulty=hard&reset=1">Difícil</a>
                    </div>
                </div>
            </div>
            
        
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const gameBoard = document.getElementById('gameBoard');
                const timerElement = document.getElementById('timer');
                let timerInterval;
                let elapsed = <?php echo $elapsedTime; ?>;
                
                function startTimer() {
                    timerInterval = setInterval(function() {
                        elapsed++;
                        const minutes = Math.floor(elapsed / 60);
                        const secs = elapsed % 60;
                        timerElement.textContent = `${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
                    }, 1000);
                }
                startTimer();
                
                // Adiciona evento de clique às cartas
                gameBoard.addEventListener('click', function(e) {
                    const card = e.target.closest('.card');
                    if (card && !card.classList.contains('flipped')) {
                        const cardIndex = card.dataset.index;
                        flipCard(cardIndex);
                    }
                });
                
                function flipCard(cardIndex) {
                    // Envia a jogada para o servidor
                    fetch('index.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=flip&card=${cardIndex}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        updateGameBoard(data);
                        
                        // Verifica se o jogo terminou
                        if (data.gameComplete) {
                            clearInterval(timerInterval);
                            setTimeout(() => {
                                alert(`Parabéns! Você completou o jogo em ${timerElement.textContent} com ${data.attempts} tentativas.`);
                            }, 500);
                        }
                    });
                }
                
                function updateGameBoard(data) {
                    // Reseta todas as cartas não encontradas
                    document.querySelectorAll('.card:not(.matched)').forEach(card => {
                        card.classList.remove('flipped');
                    });
                    
                    // Marca as cartas encontradas
                    data.matched.forEach(index => {
                        const card = document.querySelector(`.card[data-index="${index}"]`);
                        if (card) {
                            card.classList.add('flipped');
                            card.classList.add('matched');
                        }
                    });
                    
                    // Vira as cartas selecionadas
                    data.flipped.forEach(index => {
                        const card = document.querySelector(`.card[data-index="${index}"]`);
                        if (card) {
                            card.classList.add('flipped');
                        }
                    });
                }
            });
        </script>
    </body>
    </html>