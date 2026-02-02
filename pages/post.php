
<?php


function normalizarData($data) {
    // tenta formatos possíveis
    $formatos = ['Y-m-d', 'd/m/Y', 'm/d/Y'];

    foreach ($formatos as $f) {
        $d = DateTime::createFromFormat($f, $data);
        if ($d && $d->format($f) === $data) {
            return $d->format('d/m/Y'); // sempre devolve no padrão certo
        }
    }

    return null; // formato desconhecido
}


//exit("Temporary unavailable");
require('pega_origem.php');
ini_set('display_errors', 1);
error_reporting(~0);

require_once 'util/connection.php';

$pk_blognacional = pg_escape_string($_GET["post"]);


$result_posts =  pg_prepare(
    $conn,
    "sql_posts",
    " SELECT * FROM conteudo_internet.blog_nacional
where    ativo = 'true' and pk_blognacional =  $1 "
);

$result_posts = pg_execute($conn, "sql_posts", array("$pk_blognacional"));

require_once 'navegacao.php';

if (!$result_posts || pg_num_rows($result_posts) === 0) {
    echo "<h2 style='color:red; text-align:center; margin-top:40px;'>
            ❌ Post não encontrado.
          </h2>";
    exit; // importante para parar a execução e não carregar o restante do layout
}



if ($result_posts) {
    for ($rowcid = 0; $rowcid < pg_numrows($result_posts); $rowcid++) {
        $pk_blognacional  = pg_result($result_posts, $rowcid, 'pk_blognacional');
        $titulo = pg_result($result_posts, $rowcid, 'titulo');
        $data_post = pg_result($result_posts, $rowcid, 'data_post');
        $data_post_banco = pg_result($result_posts, $rowcid, 'data_post');
        $foto_capa = pg_result($result_posts, $rowcid, 'foto_capa');
        $foto_topo = pg_result($result_posts, $rowcid, 'foto_topo');
        $descritivo_blumar = pg_result($result_posts, $rowcid, 'descritivo_blumar');
        $descritivo_be = pg_result($result_posts, $rowcid, 'descritivo_be');
        $classif = pg_result($result_posts, $rowcid, 'classif');
        $url_video = pg_result($result_posts, $rowcid, 'url_video');
        $meta_description = pg_result($result_posts, $rowcid, 'meta_description');
        $ativo = pg_result($result_posts, $rowcid, 'ativo');

        $data_do_post_nova = normalizarData($data_post_banco);

        $arrayData = explode("-", $data_post);
        $diain = $arrayData[2];
        $mesin = $arrayData[1];
        $anoin = $arrayData[0];
        $data_do_post = $mesin . '/' . $diain . '/' . $anoin;

        $faqItems = array();
        $faqResult = pg_query_params(
            $conn,
            "SELECT pergunta, resposta FROM conteudo_internet.blog_nacional_faq
             WHERE post_id = $1
             ORDER BY ordem ASC, id ASC",
            array($pk_blognacional)
        );
        if ($faqResult && pg_num_rows($faqResult) > 0) {
            while ($faqRow = pg_fetch_assoc($faqResult)) {
                $faqItems[] = array(
                    'pergunta' => $faqRow['pergunta'],
                    'resposta' => $faqRow['resposta']
                );
            }
        }


         $titulo_og = htmlspecialchars($titulo, ENT_QUOTES);
    $descricao_og = htmlspecialchars($meta_description, ENT_QUOTES);

    // imagem absoluta para Facebook
    $imagem_og = "https://www.blumar.com.br/blog/images/" . $foto_topo;

    // URL atual da página
    $url_og = "https://www.blumar.com.br/blog/post.php?post=" . $pk_blognacional;

?>






<?php 

        echo '

        <div class="tag_categoria_post">
';

        if ($classif == '1') {
            echo '<a href="index.php">Home</a><span> :: </span><a href="hotels.php">Hotels</a><span> ::  ' . $titulo . '</span>';
        } elseif ($classif == '2') {
            echo '<a href="index.php">Home</a><span> :: </span><a href="tours.php">Tours</a><span> ::  ' . $titulo . '</span>';
        } elseif ($classif == '3') {
            echo '<a href="index.php">Home</a><span> :: </span><a href="boats.php">Boats</a><span> ::  ' . $titulo . '</span>';
        } elseif ($classif == '4') {
            echo '<a href="index.php">Home</a><span> :: </span><a href="flights.php">Flights</a><span> ::  ' . $titulo . '</span>';
        } elseif ($classif == '5') {
            echo '<a href="index.php">Home</a><span> :: </span><a href="destinations.php">Destinations</a><span> ::  ' . $titulo . '</span>';
        } elseif ($classif == '6') {
            echo '<a href="index.php">Home</a><span> :: </span><a href="festivals.php">Festivals</a><span> ::  ' . $titulo . '</span>';
        }

        echo '
</div>';

        echo '

<div id="content_blog">
        <div class="content_principal_post">';

        echo ' <h1> ' . $titulo . '</h1>';
        echo '<img class="foto_topo" src="images/' . $foto_topo . '">';
        echo '<p class="data_post">' . $data_do_post . '</p>';
        echo '<div class="post_texto"> ' . $descritivo_blumar . '</div>';
        // echo 'Descritivo Be  '.$descritivo_be.''; 
        
        // echo 'Foto topo  <img src="images/'.$foto_topo.'">'; 



        if (strlen($url_video) != 0) {
            echo '<div class="video-blog"> <iframe src="' . $url_video . '" frameborder="0" allowfullscreen></iframe> </div>';
        }





        
        if (!empty($faqItems)) {
            echo '<div class="faq-section">';
            echo '<h3>Perguntas frequentes</h3>';
            echo '<div class="faq-list">';
            foreach ($faqItems as $index => $item) {
                $q = htmlspecialchars($item['pergunta'], ENT_QUOTES);
                $a = $item['resposta'];
                echo '
                <div class="faq-item">
                    <button class="faq-question" type="button" aria-expanded="false">
                        <span>' . $q . '</span>
                        <span class="faq-icon">+</span>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">' . $a . '</div>
                    </div>
                </div>';
            }
            echo '</div></div>';
        }

        echo '<div class="meta_description"><h5>Metas - </h5><span>' . $meta_description . '</span></div>

        

      </div>
      
      <div class="content_menu_lateral">
        <h3>LATESTS POSTS</h3>';
        require_once 'ultimos.php';
        echo '</div>

</div>';
    }
}
?>
<style>
.faq-section {
    margin: 32px 0 24px;
    padding: 24px;
    border-radius: 18px;
    background: linear-gradient(135deg, #f8fafc 0%, #eef2f7 100%);
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
}
.faq-section h3 {
    margin: 0 0 16px;
    font-size: 22px;
    letter-spacing: 0.2px;
    color: #0f172a;
}
.faq-list {
    display: grid;
    gap: 10px;
}
.faq-item {
    border-radius: 14px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.faq-item.open {
    border-color: #94a3b8;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
}
.faq-question {
    width: 100%;
    text-align: left;
    padding: 14px 16px;
    background: transparent;
    border: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-weight: 600;
    font-size: 15px;
    color: #0f172a;
    cursor: pointer;
}
.faq-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #0f172a;
    color: #ffffff;
    font-weight: 700;
    transition: transform 0.2s ease;
}
.faq-item.open .faq-icon {
    transform: rotate(45deg);
}
.faq-answer {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.25s ease;
}
.faq-answer-inner {
    padding: 0 16px 16px;
    color: #334155;
    font-size: 14px;
    line-height: 1.55;
}
</style>
<script src="https://unpkg.com/pica@8/dist/pica.min.js"></script>
<script>
    // document.addEventListener('DOMContentLoaded', function() {
    //     const prefix = 'https://intranet.blumar.com.br/nova_intra/admin/conteudo/';
    //     const postTextoDiv = document.querySelector('.post_texto');

    //     if (postTextoDiv) {
    //         // Substituir URLs das imagens
    //         const images = postTextoDiv.querySelectorAll('img');
    //         images.forEach(function(img) {
    //             if (img.src && img.src.includes('blogv2')) {
    //                 img.src = img.src.replace('https://www.blumar.com.br/blog/', prefix);
    //             }
    //         });

    //         // Processar elementos com classe creditos_capa
    //         const creditosElements = postTextoDiv.querySelectorAll('.creditos_capa');

    //         creditosElements.forEach(function(element, index) {
    //             if (index === 0) {
    //                 // Mantém a primeira ocorrência como está
    //                 return;
    //             }

    //             // Para as demais ocorrências:
    //             // 1. Aplica font-size 10px
    //             element.style.fontSize = '10px';

    //             // 2. Remove a classe creditos_capa
    //             element.classList.remove('creditos_capa');
    //         });

    //         // Processar parágrafos com "Photo:" (com ou sem <em>)
    //         const allParagraphs = postTextoDiv.querySelectorAll('p');
    //         let photoCount = 0;

    //         allParagraphs.forEach(function(p) {
    //             const text = p.textContent.trim();

    //             // Verifica se o texto começa com "Photo:"
    //             if (/^photo:/i.test(text)) {
    //                 photoCount++;

    //                 // Aplica font-size 10px apenas depois da primeira ocorrência
    //                 if (photoCount > 1) {
    //                     // Se tem <em>, aplica no <em>
    //                     const em = p.querySelector('em');
    //                     if (em) {
    //                         em.style.fontSize = '10px';
    //                     } else {
    //                         // Caso contrário, aplica direto no <p>
    //                         p.style.fontSize = '10px';
    //                     }
    //                 }
    //             }
    //         });
    //     }
    // });
</script>

<?php 

$data_limite = DateTime::createFromFormat('d/m/Y', '22/10/2025');

// converte a data do post
$data_convertida = DateTime::createFromFormat('d/m/Y', $data_do_post_nova );

// verifica se a data é válida e se é maior que a data limite
$carregar_js = $data_convertida && $data_convertida > $data_limite;





?>



<?php if ($carregar_js): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {        
        
    const prefix = 'https://www.blumar.com.br/blog/uploads/';
    const postTextoDiv = document.querySelector('.post_texto');

    if (postTextoDiv) {

        /* ----------------------------------------
         * 1) SUBSTITUIR TODAS AS IMAGENS E TORNÁ-LAS RESPONSIVAS
         * -------------------------------------- */
        const images = postTextoDiv.querySelectorAll('img');

        images.forEach(function(img) {
            if (img.src) {
                // Extrai o nome do arquivo final
                const filename = img.src.split('/').pop();

                // Atualiza o src para a pasta correta
                img.src = prefix + filename;

                // Torna a imagem responsiva
                img.style.maxWidth = '100%';
                img.style.height = 'auto';
                img.style.display = 'block';
                
                // Opcional: adicionar margem para espaçamento
                // img.style.margin = '20px 0';
            }
        });

        /* ----------------------------------------
         * 2) TRATAR CRÉDITOS (creditos_capa)
         * -------------------------------------- */
        const creditosElements = postTextoDiv.querySelectorAll('.creditos_capa');

        creditosElements.forEach(function(element, index) {
            if (index === 0) {
                // Mantém a primeira ocorrência como está
                return;
            }

            // Para as demais ocorrências:
            element.style.fontSize = '10px';
            element.classList.remove('creditos_capa');
        });

        /* ----------------------------------------
         * 3) TRATAR "Photo:" (com ou sem <em>)
         * -------------------------------------- */
        const allParagraphs = postTextoDiv.querySelectorAll('p');
        let photoCount = 0;

        allParagraphs.forEach(function(p) {
            const text = p.textContent.trim();

            if (/^photo:/i.test(text)) {
                photoCount++;

                // Aplica font-size 10px apenas após a primeira ocorrência
                if (photoCount > 1) {
                    const em = p.querySelector('em');
                    if (em) {
                        em.style.fontSize = '10px';
                    } else {
                        p.style.fontSize = '10px';
                    }
                }
            }
        });
    }
});
<?php endif; ?>
</script>


<script>
document.addEventListener("DOMContentLoaded", function() {
    const postArea = document.querySelector('.post_texto') || document.querySelector('#player');
    if (!postArea) return;

    // Seleciona vídeos
    const videos = postArea.querySelectorAll("iframe, video");

    videos.forEach(video => {
        // Evita duplicar wrapper
        if (video.parentElement.classList.contains('responsive-video')) return;

        const wrapper = document.createElement("div");
        wrapper.className = "responsive-video";

        // Move o vídeo para dentro do wrapper
        video.parentNode.insertBefore(wrapper, video);
        wrapper.appendChild(video);
    });
});
</script>
<script async src="https://www.instagram.com/embed.js"></script>
</html>

