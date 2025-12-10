<?php

session_start();

try {
    $db = new PDO('sqlite:shagai.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
} catch(PDOException $e) {
    die("Датабааз алдаа: " . $e->getMessage());
}

$error = '';
$success = '';
$page = isset($_GET['page']) ? $_GET['page'] : 'home';


if (isset($_POST['signup'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Бүх талбарыг бөглөнө үү!';
    } elseif (strlen($username) < 3) {
        $error = 'Нэвтрэх нэр дор хаяж 3 тэмдэгт байх ёстой!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'И-мэйл хаяг буруу байна!';
    } elseif (strlen($password) < 6) {
        $error = 'Нууц үг дор хаяж 6 тэмдэгт байх ёстой!';
    } elseif ($password !== $confirm) {
        $error = 'Нууц үг таарахгүй байна!';
    } else {
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $error = 'Энэ нэвтрэх нэр эсвэл и-мэйл аль хэдийн бүртгэгдсэн байна!';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            
            if ($stmt->execute([$username, $email, $hashed])) {
                $success = 'Амжилттай бүртгэгдлээ! Та одоо нэвтэрч болно.';
                $page = 'login';
            }
        }
    }
}

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Бүх талбарыг бөглөнө үү!';
    } else {
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: ?page=home');
            exit();
        } else {
            $error = 'Нэвтрэх нэр эсвэл нууц үг буруу байна!';
        }
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ?page=home');
    exit();
}

$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : '';
?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Шагай Web</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        header {
            background: #333;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .user-info a {
            color: #fff;
            text-decoration: none;
            background: #555;
            padding: 8px 15px;
            border-radius: 5px;
        }
        .user-info a:hover {
            background: #777;
        }
        nav {
            background: #444;
            padding: 10px 20px;
        }
        nav a {
            color: white;
            margin-right: 15px;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 3px;
        }
        nav a:hover, nav a.active {
            background: #555;
        }
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .auth-box {
            max-width: 400px;
            margin: 50px auto;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: bold;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            opacity: 0.9;
        }
        .error {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .success {
            background: #efe;
            color: #3c3;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .links {
            text-align: center;
            margin-top: 20px;
        }
        .links a {
            color: #667eea;
            text-decoration: none;
        }
        .welcome-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        ul {
            line-height: 2;
            margin-left: 20px;
        }
        footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 50px;
        }
        .game-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .game-item {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s ease;
        }
        .game-item:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
            transform: translateY(-5px);
        }
        .game-item h3 {
            color: #667eea;
            margin-bottom: 10px;
        }
        .game-link {
            display: inline-block;
            margin-top: 10px;
            color: #667eea;
            text-decoration: none;
            font-weight: bold;
            padding: 8px 15px;
            border: 2px solid #667eea;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .game-link:hover {
            background: #667eea;
            color: white;
        }
    </style>
</head>
<body>
    <header>
        <h1>🎲 Шагай Web</h1>
        <div class="user-info">
            <?php if ($is_logged_in): ?>
                <span>Сайн байна уу, <strong><?php echo htmlspecialchars($username); ?></strong>!</span>
                <a href="?logout=1">Гарах</a>
            <?php else: ?>
                <a href="?page=login">Нэвтрэх</a>
                <a href="?page=signup">Бүртгүүлэх</a>
            <?php endif; ?>
        </div>
    </header>
    
    <?php if ($page != 'login' && $page != 'signup'): ?>
    <nav>
        <a href="?page=home" class="<?php echo $page == 'home' ? 'active' : ''; ?>">Нүүр</a>
        <a href="?page=about" class="<?php echo $page == 'about' ? 'active' : ''; ?>">Бидний тухай</a>
        <a href="?page=article" class="<?php echo $page == 'article' ? 'active' : ''; ?>">Шагай тоглоом</a>
    </nav>
    <?php endif; ?>

    <?php if ($page == 'login'): ?>
        <!-- НЭВТРЭХ ХУУДАС -->
        <div class="auth-box">
            <h2 style="text-align:center; margin-bottom:30px;">Нэвтрэх</h2>
            
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Нэвтрэх нэр эсвэл И-мэйл:</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Нууц үг:</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" name="login">Нэвтрэх</button>
            </form>
            
            <div class="links">
                <p>Бүртгэлгүй юу? <a href="?page=signup">Бүртгүүлэх</a></p>
                <p><a href="?page=home">Нүүр хуудас руу буцах</a></p>
            </div>
        </div>
        
    <?php elseif ($page == 'signup'): ?>
        <div class="auth-box">
            <h2 style="text-align:center; margin-bottom:30px;">Бүртгүүлэх</h2>
            
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Нэвтрэх нэр:</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>И-мэйл:</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Нууц үг:</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Нууц үг давтах:</label>
                    <input type="password" name="confirm_password" required>
                </div>
                <button type="submit" name="signup">Бүртгүүлэх</button>
            </form>
            
            <div class="links">
                <p>Бүртгэлтэй юу? <a href="?page=login">Нэвтрэх</a></p>
                <p><a href="?page=home">Нүүр хуудас руу буцах</a></p>
            </div>
        </div>
        
    <?php elseif ($page == 'home'): ?>
        <div class="container">
            <?php if ($is_logged_in): ?>
                <div class="welcome-box">
                    <h2>🎉 Тавтай морил, <?php echo htmlspecialchars($username); ?>!</h2>
                    <p>Та амжилттай нэвтэрлээ. Одоо та бүх хуудсуудыг үзэх боломжтой.</p>
                </div>
            <?php else: ?>
                <div class="welcome-box">
                    <h2>🎲 Монгол уламжлалт шагайн тоглоомын ертөнцөд тавтай морилно уу!</h2>
                    <p>Та өөрийн данс үүсгэж, шагайн тухай илүү их мэдээлэл авна уу.</p>
                </div>
            <?php endif; ?>
            
            <h2>Шагайн тухай</h2>
            <p>Шагай бол Монгол хүмүүсийн уламжлалт тоглоом юм. Энэ нь хонины шагайн ясаар тоглодог бөгөөд дөрвөн талтай:</p>
            <ul>
                <li><strong>Хонь</strong> - Хавтгай тал</li>
                <li><strong>Ямаа</strong> - Гадуур нуруутай тал</li>
                <li><strong>Тэмээ</strong> - Дотогш нуруутай тал</li>
                <li><strong>Морь</strong> - Босоо тал</li>
            </ul>
            
            <h3 style="margin-top: 30px;">Сонирхолтой мэдээлэл</h3>
            <div style="background: #f9f9f9; padding: 20px; border-radius: 10px; margin: 20px 0;">
                <p>✨ Шагай тоглоом нь 2000 гаруй жилийн түүхтэй</p>
                <p>✨ 10 гаруй төрлийн тоглоомыг шагайгаар тоглодог</p>
                <p>✨ ЮНЕСКО-гийн биет бус соёлын өвд бүртгэгдсэн</p>
                <p>✨ Хүүхдийн хөгжилд маш их ач холбогдолтой</p>
            </div>
            
            <div style="margin-top: 30px; text-align: center;">
                <a href="?page=article" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;">
                    Шагайн тоглоомуудтай танилцах →
                </a>
            </div>
        </div>
        
    <?php elseif ($page == 'about'): ?>
        <div class="container">
            <h2>📖 Бидний тухай</h2>
            
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; margin: 20px 0;">
                <h3 style="color: white;">Манай эрхэм зорилго</h3>
                <p>Бид Монголын уламжлалт соёл, тухайлбал шагайн тоглоомыг хадгалж, хойч үедээ өвлүүлэх зорилготой.</p>
            </div>
            
            <h3>Бидний үйл ажиллагаа</h3>
            <ul>
                <li><strong>Шагайн тоглоомын архив:</strong> 10+ төрлийн шагайн тоглоомын дэлгэрэнгүй тайлбар</li>
                <li><strong>Сургалт:</strong> Хүүхдүүдэд уламжлалт тоглоом заах материал</li>
                <li><strong>Өв соёлыг хадгалах:</strong> ЮНЕСКО-гийн биет бус соёлын өвийг дэмжих</li>
                <li><strong>Онлайн нийгэмлэг:</strong> Шагайн тоглоомыг сонирхогчдыг нэгтгэх</li>
            </ul>
            
            <h3 style="margin-top: 30px;">Холбоо барих</h3>
            <div style="background: #f9f9f9; padding: 20px; border-radius: 10px; margin: 20px 0;">
                <p>📧 <strong>И-мэйл:</strong> info@shagaiweb.mn</p>
                <p>📱 <strong>Утас:</strong> +976 8888-8888</p>
                <p>📍 <strong>Хаяг:</strong> Улаанбаатар хот, Монгол улс</p>
            </div>
            
            <h3 style="margin-top: 30px;">Хамтрагч байгууллагууд</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                <div style="background: white; padding: 20px; border: 2px solid #667eea; border-radius: 10px; text-align: center;">
                    <h4>🏛️ ЮНЕСКО Монгол</h4>
                    <p>Биет бус соёлын өвийг хамгаалах</p>
                    <a href="https://www.unesco.org" target="_blank" style="color: #667eea; text-decoration: none; font-weight: bold;">Вэбсайт →</a>
                </div>
                
                <div style="background: white; padding: 20px; border: 2px solid #667eea; border-radius: 10px; text-align: center;">
                    <h4>🎓 Соёл урлагийн яам</h4>
                    <p>Уламжлалт соёлыг дэмжих</p>
                    <a href="https://mccs.gov.mn" target="_blank" style="color: #667eea; text-decoration: none; font-weight: bold;">Вэбсайт →</a>
                </div>
                
                <div style="background: white; padding: 20px; border: 2px solid #667eea; border-radius: 10px; text-align: center;">
                    <h4>🎪 Монголын үндэсний музей</h4>
                    <p>Түүхийн дурсгалт зүйлс</p>
                    <a href="https://www.nationalmuseum.mn" target="_blank" style="color: #667eea; text-decoration: none; font-weight: bold;">Вэбсайт →</a>
                </div>
            </div>
            
            <?php if ($is_logged_in): ?>
                <div style="margin-top: 40px; padding: 20px; background: #e8f5e9; border-radius: 10px;">
                    <p style="text-align: center;"><em>✅ Та <strong><?php echo htmlspecialchars($username); ?></strong> нэртэйгээр нэвтэрсэн байна.</em></p>
                </div>
            <?php else: ?>
                <div style="margin-top: 40px; padding: 20px; background: #fff3e0; border-radius: 10px; text-align: center;">
                    <p><strong>Та бүртгүүлээгүй байна уу?</strong></p>
                    <a href="?page=signup" style="display: inline-block; margin-top: 10px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 25px; text-decoration: none; border-radius: 8px; font-weight: bold;">
                        Бүртгүүлэх →
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
    <?php elseif ($page == 'article'): ?>
        <div class="container">
            <h2>🎲 Шагай тоглох 10 төрлийн арга</h2>
            <div class="game-list">
                <div class="game-item">
                    <h3>1. Мэлхий өрөх</h3>
                    <p>Шагайгаар мэлхий шиг өрөөдөх тоглоом.</p>
                    <a href="https://www.mnb.mn/i/201281" target="_blank" class="game-link">Дэлгэрэнгүй унших →</a>
                </div>
                
                <div class="game-item">
                    <h3>2. Дөрвөн бэрх</h3>
                    <p>Шагайн дөрвөн талыг ашиглан тоглох.</p>
                    <a href="https://kidstoy.mn/shagain-merge/" target="_blank" class="game-link">Дэлгэрэнгүй унших →</a>
                </div>
                
                <div class="game-item">
                    <h3>3. Морь уралдуулах</h3>
                    <p>Шагайгаар морь уралдуулах тоглоом.</p>
                    <a href="https://beta.shoppy.mn/products/shagai-048" target="_blank" class="game-link">Дэлгэрэнгүй унших →</a>
                </div>
                
                <div class="game-item">
                    <h3>4. Шагай таалцах</h3>
                    <p>Шагайг таалцаж тоглох уламжлалт тоглоом.</p>
                    <a href="https://www.slideshare.net/slideshow/ss-232009178/232009178" target="_blank" class="game-link">Дэлгэрэнгүй унших →</a>
                </div>
                
                <div class="game-item">
                    <h3>5. Андгайцах шагайн наадгай</h3>
                    <p>Шагайг андгайлгаж тоглох арга.</p>
                    <a href="https://dsumiya.blogspot.com/2012/03/blog-post_5848.html" target="_blank" class="game-link">Дэлгэрэнгүй унших →</a>
                </div>
                
                <div class="game-item">
                    <h3>6. 12 жил шагайн тоглоом</h3>
                    <p>12 жилийн тэмдэглэгээтэй шагайн тоглоом.</p>
                    <a href="https://dsumiya.blogspot.com/2012/03/blog-post_5848.html" target="_blank" class="game-link">Дэлгэрэнгүй унших →</a>
                </div>
                
                <div class="game-item">
                    <h3>7. 12 бэрх шагайн наадгай</h3>
                    <p>12 бэрхтэй шагайн тоглоом.</p>
                    <a href="https://kidstoy.mn/shagain-merge/" target="_blank" class="game-link">Дэлгэрэнгүй унших →</a>
                </div>
                
                <div class="game-item">
                    <h3>8. 9 шарга</h3>
                    <p>9 шаргатай шагайн тоглоом.</p>
                    <a href="https://kidstoy.mn/shagai-togloh-arga/" target="_blank" class="game-link">Дэлгэрэнгүй унших →</a>
                </div>
                
                <div class="game-item">
                    <h3>9. Бөгцөг няслах / Шагай няслах</h3>
                    <p>Шагайг няслаж тоглох арга.</p>
                    <a href="https://ikon.mn/n/phh" target="_blank" class="game-link">Дэлгэрэнгүй унших →</a>
                </div>
                
                <div class="game-item">
                    <h3>10. Шагай шүүрэх / Дүрс бүтээх</h3>
                    <p>Шагайгаар янз бүрийн дүрс бүтээх.</p>
                    <a href="https://www.mnb.mn/i/192045" target="_blank" class="game-link">Дэлгэрэнгүй унших →</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <footer>
        <p>© 2024 Шагай Web - Монгол уламжлалт тоглоом</p>
    </footer>
</body>
</html>
