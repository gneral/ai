<?php
// result.php - Sonuç sayfası
ob_start(); // Çıktı tamponlamayı başlat
session_start();

// Oturumda hikaye ve görsel verilerini kontrol et
if (!isset($_SESSION['story']) || !isset($_SESSION['images'])) {
    header("Location: index.php");
    ob_end_flush(); // Çıktı tamponlamayı bitir
    exit();
}

$story = $_SESSION['story'];
$images = $_SESSION['images'];
$prompt = $_SESSION['prompt'] ?? '';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hikaye ve Görseller - Gemini 2.0 ile Hikaye ve Görsel Oluşturucu</title>
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
        
        .story-header {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            padding: 2rem 0;
            border-radius: 15px;
            margin-top: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .story-header h1 {
            color: white;
            font-weight: 700;
        }
        
        .story-content {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .story-image {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin: 1.5rem 0;
        }
        
        .story-image img {
            width: 100%;
            height: auto;
            display: block;
        }
        
        .story-text {
            font-size: 1.1rem;
            line-height: 1.7;
            margin-bottom: 1.5rem;
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
        
        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-2px);
        }
        
        .prompt-container {
            background-color: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .prompt-text {
            font-family: monospace;
            white-space: pre-wrap;
            margin: 0;
        }
        
        footer {
            background-color: white;
            padding: 1.5rem 0;
            border-top: 1px solid #e5e7eb;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="story-header text-center p-4">
            <h1><i class="fas fa-book-open me-2"></i> Oluşturulan Hikaye ve Görseller</h1>
        </div>
        
        <div class="story-content">
            <?php
            // Paragrafları ayır
            $paragraphs = explode("\n\n", $story);
            $imageCount = count($images);
            $imageIndex = 0;
            
            // Paragrafların ve görsellerin uygun şekilde yerleştirilmesi
            foreach ($paragraphs as $index => $paragraph) {
                echo '<div class="story-text">' . $paragraph . '</div>';
                
                // Her paragraftan sonra bir resim göster (eğer yeterli resim varsa)
                if ($imageIndex < $imageCount && ($index == 0 || $index == floor(count($paragraphs) / 2))) {
                    echo '<div class="story-image"><img src="' . $images[$imageIndex] . '" alt="Hikaye Görseli"></div>';
                    $imageIndex++;
                }
            }
            
            // Kalan resimleri göster
            while ($imageIndex < $imageCount) {
                echo '<div class="story-image"><img src="' . $images[$imageIndex] . '" alt="Hikaye Görseli"></div>';
                $imageIndex++;
            }
            ?>
            
            <div class="mt-4">
                <h5><i class="fas fa-code me-2"></i> Kullanılan Prompt:</h5>
                <div class="prompt-container">
                    <pre class="prompt-text"><?php echo htmlspecialchars($prompt); ?></pre>
                </div>
            </div>
            
            <div class="d-flex justify-content-between mt-5">
                <a href="index.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i> Yeni Bir Hikaye Oluştur
                </a>
                
                <button class="btn btn-primary" onclick="window.print();">
                    <i class="fas fa-print me-2"></i> Yazdır / Kaydet
                </button>
            </div>
        </div>
        
        <footer class="text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Gemini 2.0 Flash Hikaye ve Görsel Oluşturucu</p>
        </footer>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
