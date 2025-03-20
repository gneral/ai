<?php
// index.php - Ana sayfa
ob_start(); // Çıktı tamponlamayı başlat
session_start();
include 'config.php';

// Form gönderildiğinde
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $storyTheme = $_POST['story_theme'] ?? '';
    $storyLength = $_POST['story_length'] ?? 'medium';
    $imageStyle = $_POST['image_style'] ?? 'realistic';
    $characterType = $_POST['character_type'] ?? '';
    $setting = $_POST['setting'] ?? '';
    $mood = $_POST['mood'] ?? 'neutral';
    $language = $_POST['language'] ?? 'tr';
    
    // Hikaye ve görsel oluşturmak için prompt
    $basePrompt = "generate a story with images";
    $fullPrompt = "$basePrompt about $storyTheme with $characterType characters in a $setting setting with a $mood mood";
    
    // Hikaye uzunluğu ekle
    switch ($storyLength) {
        case 'short':
            $fullPrompt .= ". Make it a short story with 1-2 paragraphs and 4 image";
            $numImages = 1;
            break;
        case 'medium':
            $fullPrompt .= ". Make it a medium-length story with 3-4 paragraphs and 6 images";
            $numImages = 2;
            break;
        case 'long':
            $fullPrompt .= ". Make it a longer story with 5-7 paragraphs and 12 images";
            $numImages = 3;
            break;
    }
    
    // Görsel stili ekle
    $fullPrompt .= ". Generate images in $imageStyle style";
    
    // Dil seçimi
    $fullPrompt .= ". Write the story in " . ($language == 'tr' ? 'Turkish' : 'English');
    
    // API isteği
    try {
        // Gemini API'ye istek gönder
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $apiKey;
        $data = [
            'contents' => [
                'parts' => [
                    ['text' => $fullPrompt]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 8192,
            ],
            'safetySettings' => [
                ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'],
                ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'],
                ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
                ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE']
            ]
        ];
        
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $response = json_decode($result, true);
        
        // API yanıtından hikayeyi ve görselleri ayıkla
        $story = "";
        $images = [];
        
        if (isset($response['candidates'][0]['content']['parts'])) {
            $parts = $response['candidates'][0]['content']['parts'];
            
            foreach ($parts as $part) {
                if (isset($part['text'])) {
                    $story .= $part['text'] . "\n\n";
                }
                if (isset($part['inlineData']['mimeType']) && 
                    strpos($part['inlineData']['mimeType'], 'image/') === 0) {
                    $imageData = $part['inlineData']['data']; // Base64 formatında
                    $images[] = "data:" . $part['inlineData']['mimeType'] . ";base64," . $imageData;
                }
            }
        }
        
        // Eğer API yanıtı boşsa veya hata varsa
        if (empty($story)) {
            // Mock veri kullan (sadece hata durumlarında)
            $story = "Bir zamanlar $setting'de yaşayan $characterType vardı. Onlar $mood bir yaşam sürüyorlardı...";
            $images = [];
            for ($i = 0; $i < $numImages; $i++) {
                $images[] = "https://via.placeholder.com/800x450.png?text=" . urlencode("API yanıtı alınamadı");
            }
        }
        
        $_SESSION['story'] = $story;
        $_SESSION['images'] = $images;
        $_SESSION['prompt'] = $fullPrompt;
        
        // Sonuç sayfasına yönlendir
        header("Location: result.php");
        ob_end_flush(); // Çıktı tamponlamayı bitir
        exit();
    } catch (Exception $e) {
        $error = "API isteği sırasında bir hata oluştu: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gemini 2.0 ile Hikaye ve Görsel Oluşturucu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-light: #818cf8;
            --primary-dark: #4f46e5;
            --secondary: #f59e0b;
            --dark: #1f2937;
            --light: #f9fafb;
        }
        
        body {
            background-color: #f3f4f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--dark);
        }
        
        .container {
            max-width: 1000px;
        }
        
        .hero {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            padding: 3rem 0;
            border-radius: 15px;
            margin-top: 2rem;
            margin-bottom: 3rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .hero h1 {
            color: white;
            font-weight: 700;
        }
        
        .hero p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
        }
        
        .form-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .form-card h3 {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #4b5563;
        }
        
        .form-control, .form-select {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn-lg {
            font-size: 1.1rem;
            padding: 1rem 2rem;
        }
        
        .icon-box {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .icon-box i {
            font-size: 1.5rem;
            color: var(--primary);
            margin-right: 1rem;
            width: 40px;
            text-align: center;
        }
        
        footer {
            background-color: white;
            padding: 1.5rem 0;
            border-top: 1px solid #e5e7eb;
            margin-top: 2rem;
        }
        
        .style-preview {
            display: inline-block;
            width: 100%;
            height: 80px;
            margin-top: 5px;
            border-radius: 8px;
            background-size: cover;
            background-position: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .style-preview:hover {
            transform: scale(1.05);
        }
        
        .tooltip-container {
            position: relative;
            display: inline-block;
            margin-left: 10px;
        }
        
        .tooltip-icon {
            color: var(--primary-light);
            cursor: pointer;
        }
        
        .tooltip-text {
            visibility: hidden;
            width: 200px;
            background-color: var(--dark);
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .tooltip-container:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="hero text-center p-5">
            <h1><i class="fas fa-magic me-2"></i> Gemini 2.0 Flash ile Hikaye ve Görsel Oluşturucu</h1>
            <p class="lead mt-3">Hayal ettiğiniz hikayeyi yazın, yapay zeka sizin için görsellerle zenginleştirilmiş bir hikaye oluştursun!</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="form-card">
                <h3><i class="fas fa-book me-2"></i> Hikaye Detayları</h3>
                
                <div class="row">
                    <div class="col-md-12 form-group">
                        <label for="story_theme" class="form-label">Hikaye Teması veya Konusu</label>
                        <input type="text" class="form-control" id="story_theme" name="story_theme" placeholder="Örn: Gizemli bir orman macerası, Uzay keşfi..." required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="character_type" class="form-label">Karakter Türü</label>
                        <input type="text" class="form-control" id="character_type" name="character_type" placeholder="Örn: Kahraman bir şövalye, Meraklı çocuklar...">
                    </div>
                    
                    <div class="col-md-6 form-group">
                        <label for="setting" class="form-label">Hikaye Ortamı</label>
                        <input type="text" class="form-control" id="setting" name="setting" placeholder="Örn: Antik bir kale, Futuristik bir şehir...">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="story_length" class="form-label">Hikaye Uzunluğu</label>
                        <div class="tooltip-container">
                            <i class="fas fa-info-circle tooltip-icon"></i>
                            <span class="tooltip-text">Kısa: 1-2 paragraf ve 1 görsel, Orta: 3-4 paragraf ve 2 görsel, Uzun: 5-7 paragraf ve 3 görsel</span>
                        </div>
                        <select class="form-select" id="story_length" name="story_length">
                            <option value="short">Kısa</option>
                            <option value="medium" selected>Orta</option>
                            <option value="long">Uzun</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 form-group">
                        <label for="mood" class="form-label">Hikaye Tonu/Ruh Hali</label>
                        <select class="form-select" id="mood" name="mood">
                            <option value="happy">Mutlu/Neşeli</option>
                            <option value="adventurous">Macera Dolu</option>
                            <option value="mysterious">Gizemli</option>
                            <option value="dramatic">Dramatik</option>
                            <option value="funny">Komik</option>
                            <option value="scary">Korkutucu</option>
                            <option value="educational">Eğitici</option>
                            <option value="magical">Büyülü</option>
                            <option value="neutral" selected>Nötr</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-card">
                <h3><i class="fas fa-image me-2"></i> Görsel Stili</h3>
                
                <div class="row">
                    <div class="col-md-12 form-group">
                        <label class="form-label">Görsel Stili Seçin</label>
                        <div class="tooltip-container">
                            <i class="fas fa-info-circle tooltip-icon"></i>
                            <span class="tooltip-text">Gemini 2.0 Flash tarafından desteklenen görsel stilleri</span>
                        </div>
                        
                        <div class="row mt-2">
                            <div class="col-md-3 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="image_style" id="style_realistic" value="realistic" checked>
                                    <label class="form-check-label" for="style_realistic">Gerçekçi</label>
                                    <div class="style-preview" style="background-image: url('https://via.placeholder.com/400x300.png?text=Gerçekçi')"></div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="image_style" id="style_cartoon" value="cartoon">
                                    <label class="form-check-label" for="style_cartoon">Çizgi Film</label>
                                    <div class="style-preview" style="background-image: url('https://via.placeholder.com/400x300.png?text=Çizgi+Film')"></div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="image_style" id="style_disney" value="disney style">
                                    <label class="form-check-label" for="style_disney">Disney Tarzı</label>
                                    <div class="style-preview" style="background-image: url('https://via.placeholder.com/400x300.png?text=Disney')"></div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="image_style" id="style_pixar" value="pixar style">
                                    <label class="form-check-label" for="style_pixar">Pixar Tarzı</label>
                                    <div class="style-preview" style="background-image: url('https://via.placeholder.com/400x300.png?text=Pixar')"></div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="image_style" id="style_anime" value="anime">
                                    <label class="form-check-label" for="style_anime">Anime</label>
                                    <div class="style-preview" style="background-image: url('https://via.placeholder.com/400x300.png?text=Anime')"></div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="image_style" id="style_watercolor" value="watercolor">
                                    <label class="form-check-label" for="style_watercolor">Suluboya</label>
                                    <div class="style-preview" style="background-image: url('https://via.placeholder.com/400x300.png?text=Suluboya')"></div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="image_style" id="style_oil_painting" value="oil painting">
                                    <label class="form-check-label" for="style_oil_painting">Yağlı Boya</label>
                                    <div class="style-preview" style="background-image: url('https://via.placeholder.com/400x300.png?text=Yağlı+Boya')"></div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="image_style" id="style_3d" value="3d render">
                                    <label class="form-check-label" for="style_3d">3D Render</label>
                                    <div class="style-preview" style="background-image: url('https://via.placeholder.com/400x300.png?text=3D+Render')"></div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="image_style" id="style_pixel_art" value="pixel art">
                                    <label class="form-check-label" for="style_pixel_art">Piksel Sanatı</label>
                                    <div class="style-preview" style="background-image: url('https://via.placeholder.com/400x300.png?text=Piksel+Sanatı')"></div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="image_style" id="style_comic" value="comic book">
                                    <label class="form-check-label" for="style_comic">Çizgi Roman</label>
                                    <div class="style-preview" style="background-image: url('https://via.placeholder.com/400x300.png?text=Çizgi+Roman')"></div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="image_style" id="style_sketch" value="pencil sketch">
                                    <label class="form-check-label" for="style_sketch">Karakalem</label>
                                    <div class="style-preview" style="background-image: url('https://via.placeholder.com/400x300.png?text=Karakalem')"></div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="image_style" id="style_minimalist" value="minimalist">
                                    <label class="form-check-label" for="style_minimalist">Minimalist</label>
                                    <div class="style-preview" style="background-image: url('https://via.placeholder.com/400x300.png?text=Minimalist')"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-card">
                <h3><i class="fas fa-globe me-2"></i> Diğer Ayarlar</h3>
                
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="language" class="form-label">Hikaye Dili</label>
                        <select class="form-select" id="language" name="language">
                            <option value="tr" selected>Türkçe</option>
                            <option value="en">İngilizce</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4 mb-5">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-wand-magic-sparkles me-2"></i> Hikaye ve Görselleri Oluştur
                </button>
            </div>
        </form>
        
        <footer class="text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Gemini 2.0 Flash Hikaye ve Görsel Oluşturucu</p>
        </footer>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

