from flask import Flask, request, jsonify, send_file
from flask_cors import CORS
import os
from pathlib import Path
from dotenv import load_dotenv
import shutil
from datetime import datetime
from PIL import Image


load_dotenv()

app = Flask(__name__)
CORS(app)

BASE_PATH = os.getenv("BASE_PATH", "Z:\\wwwinternet\\bancodeimagemfotosteste")
BASE_PATH_CIDADE = os.getenv("BASE_PATH_CIDADE", "Z:\\wwwinternet\\bancoimagemfotosteste\\cidade")
BASE_PATH_HOTEL = os.getenv("BASE_PATH_HOTEL", "Z:\\wwwinternet\\bancoimagemfotosteste\\hotel")
TOKEN = os.getenv("API_TOKEN")

SIZES = {
    "thumb": (135, 90),
    "small": (300, 200),
    "med": (450, 300),
    "grd": (840, 560)
}

# Extensões de imagem permitidas
EXTENSOES_IMAGEM = {'.jpg', '.jpeg', '.png', '.gif', '.webp', '.bmp'}


def gerar_tamanhos(caminho_original, pasta, nome_base, ext):
    img = Image.open(caminho_original)
    paths = {}

    for key, (w, h) in SIZES.items():
        copia = img.copy()
        copia.thumbnail((w, h))
        novo_nome = f"{nome_base}_{key}{ext}"
        destino = os.path.join(pasta, novo_nome)
        copia.save(destino, quality=90)
        paths[key] = destino

    return paths

def registrar_log_movimentacao(origem, destino, sucesso=True, erro=None):
    """Registra no arquivo de log todas as movimentações de arquivos"""
    try:
        log_file = "movimentacoes_log.txt"
        timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

        with open(log_file, "a", encoding="utf-8") as f:
            if sucesso:
                f.write(f"[{timestamp}] SUCESSO\n")
                f.write(f"  DE: {origem}\n")
                f.write(f"  PARA: {destino}\n")
                f.write("-" * 80 + "\n")
            else:
                f.write(f"[{timestamp}] ERRO\n")
                f.write(f"  DE: {origem}\n")
                f.write(f"  PARA: {destino}\n")
                f.write(f"  ERRO: {erro}\n")
                f.write("-" * 80 + "\n")
    except Exception as e:
        # Não queremos que erro no log quebre a aplicação
        print(f"Erro ao registrar log: {e}")

def validar_token():
    """Valida o token da requisição"""
    token = request.headers.get('Authorization') or request.args.get('token')
    if token and token.replace('Bearer ', '') == TOKEN:
        return True
    return False

def sanitize_rel_path(path):
    if not path:
        return None

    path = path.strip().replace("\\", "/")

    if ".." in path or path.startswith("/"):
        return None

    parts = [p for p in path.split("/") if p]
    for p in parts:
        if not all(c.isalnum() or c in "-_" for c in p):
            return None

    return "/".join(parts)

def normalize_name(value):
    if not value:
        return ""
    name = str(value).strip().lower()
    name = "".join(c if c.isalnum() or c in "-_" else "_" for c in name)
    name = "_".join(filter(None, name.split("_")))
    return name


def gerar_tamanhos(caminho_original, pasta, nome_base, ext):
    img = Image.open(caminho_original)
    paths = {}

    for key, (w, h) in SIZES.items():
        copia = img.copy()
        copia.thumbnail((w, h))
        novo_nome = f"{nome_base}_{key}{ext}"
        destino = os.path.join(pasta, novo_nome)
        copia.save(destino, quality=90)
        paths[key] = destino

    return paths


def sanitize_rel_path(path):
    if not path:
        return None

    path = path.strip().replace("\\", "/")

    if ".." in path or path.startswith("/"):
        return None

    parts = [p for p in path.split("/") if p]
    for p in parts:
        if not all(c.isalnum() or c in "-_" for c in p):
            return None

    return "/".join(parts)


@app.route('/api/upload_from_erp_enviar_para_cidade', methods=['POST'])
def upload_from_erp_enviar_para_cidade():
    if not validar_token():
        return jsonify({"success": False, "error": "Token inválido"}), 401

    file = request.files.get("file")
    cidade_nome = request.form.get("cidade_nome", "").strip()

    print("Cidade recebida:", cidade_nome)

    if not file:
        return jsonify({"success": False, "error": "Arquivo não enviado"}), 400

    if not cidade_nome:
        return jsonify({"success": False, "error": "cidade_nome é obrigatório"}), 400

    # sanitiza
    safe_cidade = sanitize_rel_path(cidade_nome)

    if not safe_cidade:
        return jsonify({"success": False, "error": "Nome da cidade inválido"}), 400

    ext = os.path.splitext(file.filename)[1].lower()
    if ext not in EXTENSOES_IMAGEM:
        return jsonify({"success": False, "error": "Extensão não permitida"}), 400

    # caminho final: BASE/cidade
    destino_dir = os.path.join(
        BASE_PATH_CIDADE,
        safe_cidade.replace("/", os.sep)
    )

    print("Destino final:", destino_dir)
    os.makedirs(destino_dir, exist_ok=True)

    nome = Path(file.filename).stem
    nome = "".join(c if c.isalnum() or c in "-_" else "_" for c in nome)
    filename = f"{nome}_{int(datetime.now().timestamp())}{ext}"

    caminho_final = os.path.join(destino_dir, filename)

    try:
        # salva original
        file.save(caminho_final)

        # gera tamanhos (mantém dentro da pasta da cidade)
        sizes = gerar_tamanhos(caminho_final, destino_dir, nome, ext)

        # registra log com caminho correto (sem duplicação)
        registrar_log_movimentacao(
            "ERP",
            f"{safe_cidade}/{filename}",
            sucesso=True
        )

        return jsonify({
            "success": True,
            "cidade": safe_cidade,
            "original": f"{safe_cidade}/{filename}",          # ← AQUI ESTÁ A CORREÇÃO PRINCIPAL
            "sizes": {
                k: f"{safe_cidade}/{Path(v).name}"            # ← só cidade + nome do arquivo
                for k, v in sizes.items()
            },
            "full_path": caminho_final
        })

    except Exception as e:
        registrar_log_movimentacao("ERP", caminho_final, sucesso=False, erro=str(e))
        return jsonify({"success": False, "error": str(e)}), 500


@app.route('/api/upload_from_erp_enviar_para_hotel', methods=['POST'])
def upload_from_erp_enviar_para_hotel():
    if not validar_token():
        return jsonify({"success": False, "error": "Token inválido"}), 401

    file = request.files.get("file")
    cidade_nome = request.form.get("cidade_nome", "").strip()
    hotel_nome = request.form.get("hotel_nome", "").strip()

    if not file:
        return jsonify({"success": False, "error": "Arquivo não enviado"}), 400

    if not cidade_nome or not hotel_nome:
        return jsonify({"success": False, "error": "cidade_nome e hotel_nome são obrigatórios"}), 400

    cidade_slug = normalize_name(cidade_nome)
    hotel_slug = normalize_name(hotel_nome)
    safe_path = sanitize_rel_path(f"{cidade_slug}/{hotel_slug}")

    if not safe_path:
        return jsonify({"success": False, "error": "Pasta inválida"}), 400

    ext = os.path.splitext(file.filename)[1].lower()
    if ext not in EXTENSOES_IMAGEM:
        return jsonify({"success": False, "error": "Extensão não permitida"}), 400

    destino_dir = os.path.join(
        BASE_PATH_HOTEL,
        safe_path.replace("/", os.sep)
    )

    os.makedirs(destino_dir, exist_ok=True)

    nome = Path(file.filename).stem
    nome = "".join(c if c.isalnum() or c in "-_" else "_" for c in nome)
    filename = f"{nome}_{int(datetime.now().timestamp())}{ext}"

    caminho_final = os.path.join(destino_dir, filename)

    try:
        file.save(caminho_final)
        sizes = gerar_tamanhos(caminho_final, destino_dir, nome, ext)

        registrar_log_movimentacao(
            "ERP",
            f"hotel/{safe_path}/{filename}",
            sucesso=True
        )

        return jsonify({
            "success": True,
            "cidade": cidade_slug,
            "hotel": hotel_slug,
            "original": f"hotel/{safe_path}/{filename}",
            "sizes": {
                k: f"hotel/{safe_path}/{Path(v).name}"
                for k, v in sizes.items()
            },
            "full_path": caminho_final
        })

    except Exception as e:
        registrar_log_movimentacao("ERP", caminho_final, sucesso=False, erro=str(e))
        return jsonify({"success": False, "error": str(e)}), 500




@app.route('/api/categorias', methods=['GET'])
def listar_categorias():
    """Lista todas as categorias (pastas principais)"""
    # if not validar_token():
    #     return jsonify({"success": False, "error": "Token inválido"}), 401
    
    try:
        categorias = []
        for item in os.listdir(BASE_PATH):
            caminho = os.path.join(BASE_PATH, item)
            if os.path.isdir(caminho):
                # Conta quantas subpastas (cidades) existem
                subcategorias = sum(1 for x in os.listdir(caminho) if os.path.isdir(os.path.join(caminho, x)))
                
                categorias.append({
                    "nome": item,
                    "caminho": item,
                    "total_cidades": subcategorias
                })
        
        categorias.sort(key=lambda x: x['nome'])
        return jsonify({"success": True, "categorias": categorias})
    
    except Exception as e:
        return jsonify({"success": False, "error": str(e)}), 500


@app.route('/api/cidades', methods=['GET'])
def listar_cidades():
    """Lista todas as cidades (subpastas) de uma categoria"""
    if not validar_token():
        return jsonify({"success": False, "error": "Token inválido"}), 401
    
    categoria = request.args.get('categoria')
    if not categoria:
        return jsonify({"success": False, "error": "Categoria não informada"}), 400
    
    try:
        caminho_categoria = os.path.join(BASE_PATH, categoria)
        
        if not os.path.exists(caminho_categoria):
            return jsonify({"success": False, "error": "Categoria não encontrada"}), 404
        
        cidades = []
        for item in os.listdir(caminho_categoria):
            caminho = os.path.join(caminho_categoria, item)
            if os.path.isdir(caminho):
                # Conta quantas imagens existem nesta cidade
                imagens = sum(1 for x in os.listdir(caminho) 
                            if os.path.isfile(os.path.join(caminho, x)) 
                            and os.path.splitext(x)[1].lower() in EXTENSOES_IMAGEM)
                
                cidades.append({
                    "nome": item,
                    "caminho": f"{categoria}/{item}",
                    "total_imagens": imagens
                })
        
        cidades.sort(key=lambda x: x['nome'])
        return jsonify({"success": True, "cidades": cidades})
    
    except Exception as e:
        return jsonify({"success": False, "error": str(e)}), 500


@app.route('/api/imagens', methods=['GET'])
def listar_imagens():
    """Lista todas as imagens de uma cidade"""
    if not validar_token():
        return jsonify({"success": False, "error": "Token inválido"}), 401
    
    categoria = request.args.get('categoria')
    cidade = request.args.get('cidade')
    
    if not categoria or not cidade:
        return jsonify({"success": False, "error": "Categoria e cidade são obrigatórias"}), 400
    
    try:
        caminho_cidade = os.path.join(BASE_PATH, categoria, cidade)
        
        if not os.path.exists(caminho_cidade):
            return jsonify({"success": False, "error": "Cidade não encontrada"}), 404
        
        imagens = []
        for item in os.listdir(caminho_cidade):
            caminho_completo = os.path.join(caminho_cidade, item)
            if os.path.isfile(caminho_completo):
                extensao = os.path.splitext(item)[1].lower()
                if extensao in EXTENSOES_IMAGEM:
                    tamanho = os.path.getsize(caminho_completo)
                    
                    imagens.append({
                        "nome": item,
                        "caminho": f"{categoria}/{cidade}/{item}",
                        "tamanho": tamanho,
                        "tamanho_formatado": formatar_tamanho(tamanho),
                        "extensao": extensao
                    })
        
        imagens.sort(key=lambda x: x['nome'])
        return jsonify({"success": True, "imagens": imagens, "total": len(imagens)})
    
    except Exception as e:
        return jsonify({"success": False, "error": str(e)}), 500


@app.route('/api/imagem/<path:caminho>', methods=['GET'])
def obter_imagem(caminho):
    """Retorna uma imagem específica"""
    if not validar_token():
        return jsonify({"success": False, "error": "Token inválido"}), 401
    
    try:
        caminho_completo = os.path.join(BASE_PATH, caminho)
        
        if not os.path.exists(caminho_completo):
            return jsonify({"success": False, "error": "Imagem não encontrada"}), 404
        
        return send_file(caminho_completo)
    
    except Exception as e:
        return jsonify({"success": False, "error": str(e)}), 500

@app.route('/api/mover', methods=['POST'])
def mover_imagem():
    data = request.get_json()
    origem = data.get('origem')      # ex: cidade/angra/vista_aerea1_grd.jpg
    destino = data.get('destino')    # ex: hotel/vila_de_alter_pousada_boutique/imagens/12345.jpg

    if not origem or not destino:
        return jsonify({"success": False, "error": "Origem e destino são obrigatórios"}), 400

    # Remove a pasta 'imagens' ou '/imagens/' do caminho de destino
    destino = destino.replace("'imagens'/", "").replace("/imagens/", "").replace("imagens/", "")

    # Caminhos absolutos no Windows
    caminho_origem = os.path.join(BASE_PATH, origem.replace('/', os.sep))
    caminho_destino = os.path.join(BASE_PATH, destino.replace('/', os.sep))

    print("Origem  :", caminho_origem)
    print("Destino :", caminho_destino)

    if not os.path.exists(caminho_origem):
        # Registrar erro no log
        registrar_log_movimentacao(
            origem,
            destino,
            sucesso=False,
            erro="Este arquivo não está mais na pasta de origem"
        )

        return jsonify({
            "success": False,
            "error": "Este arquivo não está mais na pasta de origem",
            "caminho_buscado": caminho_origem
        }), 404

    try:
        # Garante que TODAS as pastas de destino existam
        os.makedirs(os.path.dirname(caminho_destino), exist_ok=True)

        # Move o arquivo
        shutil.move(caminho_origem, caminho_destino)

        # Registrar sucesso no log
        registrar_log_movimentacao(origem, destino, sucesso=True)

        return jsonify({
            "success": True,
            "mensagem": "Imagem movida com sucesso",
            "origem": origem,
            "destino": destino
        })

    except Exception as e:
        # Registrar erro no log
        registrar_log_movimentacao(origem, destino, sucesso=False, erro=str(e))

        return jsonify({"success": False, "error": str(e)}), 500

        
@app.route('/api/excluir', methods=['DELETE'])
def excluir_imagem():
    """Exclui uma imagem"""
    if not validar_token():
        return jsonify({"success": False, "error": "Token inválido"}), 401
    
    caminho = request.args.get('caminho')
    
    if not caminho:
        return jsonify({"success": False, "error": "Caminho não informado"}), 400
    
    try:
        caminho_completo = os.path.join(BASE_PATH, caminho)
        
        if not os.path.exists(caminho_completo):
            return jsonify({"success": False, "error": "Arquivo não encontrado"}), 404
        
        os.remove(caminho_completo)
        
        return jsonify({
            "success": True,
            "mensagem": "Imagem excluída com sucesso"
        })
    
    except Exception as e:
        return jsonify({"success": False, "error": str(e)}), 500


@app.route('/api/estatisticas', methods=['GET'])
def estatisticas():
    """Retorna estatísticas gerais do banco de imagens"""
    if not validar_token():
        return jsonify({"success": False, "error": "Token inválido"}), 401
    
    try:
        total_categorias = 0
        total_cidades = 0
        total_imagens = 0
        tamanho_total = 0
        
        for categoria in os.listdir(BASE_PATH):
            caminho_categoria = os.path.join(BASE_PATH, categoria)
            if os.path.isdir(caminho_categoria):
                total_categorias += 1
                
                for cidade in os.listdir(caminho_categoria):
                    caminho_cidade = os.path.join(caminho_categoria, cidade)
                    if os.path.isdir(caminho_cidade):
                        total_cidades += 1
                        
                        for arquivo in os.listdir(caminho_cidade):
                            caminho_arquivo = os.path.join(caminho_cidade, arquivo)
                            if os.path.isfile(caminho_arquivo):
                                extensao = os.path.splitext(arquivo)[1].lower()
                                if extensao in EXTENSOES_IMAGEM:
                                    total_imagens += 1
                                    tamanho_total += os.path.getsize(caminho_arquivo)
        
        return jsonify({
            "success": True,
            "estatisticas": {
                "total_categorias": total_categorias,
                "total_cidades": total_cidades,
                "total_imagens": total_imagens,
                "tamanho_total": tamanho_total,
                "tamanho_total_formatado": formatar_tamanho(tamanho_total)
            }
        })
    
    except Exception as e:
        return jsonify({"success": False, "error": str(e)}), 500


def formatar_tamanho(tamanho_bytes):
    """Formata o tamanho em bytes para formato legível"""
    for unidade in ['B', 'KB', 'MB', 'GB', 'TB']:
        if tamanho_bytes < 1024.0:
            return f"{tamanho_bytes:.2f} {unidade}"
        tamanho_bytes /= 1024.0
    return f"{tamanho_bytes:.2f} PB"


@app.route('/api/health', methods=['GET'])
def health():
    """Endpoint de health check"""
    return jsonify({
        "success": True,
        "status": "online",
        "base_path": BASE_PATH,
        "base_path_existe": os.path.exists(BASE_PATH)
    })


if __name__ == "__main__":
    print(f"🚀 API iniciada!")
    print(f"📁 Base Path: {BASE_PATH}")
    print(f"📁 Base Path Cidade: {BASE_PATH_CIDADE}")
    print(f"🔐 Token configurado: {'Sim' if TOKEN else 'Não'}")
    app.run(host="0.0.0.0", port=5000, debug=True)
