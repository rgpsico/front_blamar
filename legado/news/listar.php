<?php
session_start();
require_once '../util/connection.php';
// Verificação de sessão
if (!isset($_SESSION['consulta']) || $_SESSION['consulta'] != 't') {
    $readOnly = false;
} else {
    $readOnly = true;
}
?>
<link rel="stylesheet" href="newsv2/listar.css">
<input type="hidden" id="readOnly" value="<?php echo $readOnly; ?>">
<!-- Container principal com classe única para encapsulamento -->
<div class="news-admin-container">

    <!-- Modal Editar -->
    <div id="newsModalEdit" class="news-modal-overlay">
        <div class="news-modal-content-modern" style="max-width: 1200px; max-height: 90vh; overflow-y: auto;">
            <div class="news-modal-header-modern">
                <h3><i class="fas fa-edit"></i> Editar Newsletter</h3>
                <button onclick="newsFecharModalEdit()" style="position: absolute; top: 10px; right: 10px; background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <div id="newsEditContent" style="padding-right: 10px;">
                <form id="newsEditForm">
                    <input type="hidden" id="editNewsId">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <!-- Coluna 1 -->
                        <div>
                            <label class="news-label"><i class="fas fa-tag"></i> Nome da Newsletter *</label>
                            <input type="text" id="editNome" class="news-input" required>

                            <label class="news-label"><i class="fas fa-calendar"></i> Data Extenso</label>
                            <input type="text" id="editDataExtenso" class="news-input">

                            <label class="news-label"><i class="fas fa-heading"></i> Título</label>
                            <input type="text" id="editTitulo" class="news-input">

                            <label class="news-label"><i class="fas fa-file-pdf"></i> PDF <small style="color: #9ca3af;">(salvo em: /novo_site/admin/news/pdf)</small></label>
                            <input type="text" id="editPdf" class="news-input" placeholder="nome_arquivo.pdf">

                            <label class="news-label"><i class="fas fa-building"></i> Empresa</label>
                            <div style="display: flex; flex-direction: column; gap: 8px; background: rgba(255, 255, 255, 0.05); padding: 15px; border-radius: 10px;">
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="radio" name="editEmpresa" id="editEmpresa1" value="1" checked>
                                    <span>Blumar</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="radio" name="editEmpresa" id="editEmpresa2" value="2">
                                    <span>Rio Life</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="radio" name="editEmpresa" id="editEmpresa3" value="3">
                                    <span>Eventos</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="radio" name="editEmpresa" id="editEmpresa4" value="4">
                                    <span>BeBrazil</span>
                                </label>
                            </div>

                            <label class="news-label"><i class="fas fa-palette"></i> Cor PE (hex sem #)</label>
                            <input type="text" id="editCorPe" class="news-input" placeholder="F9020E">
                        </div>

                        <!-- Coluna 2 - Status -->
                        <div>
                            <label class="news-label"><i class="fas fa-toggle-on"></i> Status de Exibição</label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                <label><input type="checkbox" id="editAtivoWeb"> Ativo Web</label>
                                <label><input type="checkbox" id="editAtivoHome"> Ativo Home</label>
                                <label><input type="checkbox" id="editAtivoPassion"> Ativo Passion</label>
                                <label><input type="checkbox" id="editAtivoBe"> Ativo BE</label>
                                <label><input type="checkbox" id="editTituloAtivo"> Título Ativo</label>
                                <label><input type="checkbox" id="editRecep"> Recep</label>
                                <label><input type="checkbox" id="editHeaderItaliano"> Topo Italiano</label>
                                <label><input type="checkbox" id="editNovoLayout"> Novo Layout</label>
                            </div>
                        </div>
                    </div>

                    <hr style="margin: 30px 0; border-color: var(--news-border-color);">

                    <!-- Imagens -->
                    <h4 style="margin: 20px 0 15px; color: #5a67d8;"><i class="fas fa-images"></i> Imagens</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <div>
                            <label class="news-label">Imagem Topo</label>
                            <input type="text" id="editImgTopo" class="news-input" placeholder="caminho/relativo.jpg">
                            <small style="color: #9ca3af;">Alt: <input type="text" id="editAltTopo" style="width: 100%; margin-top: 5px;"></small>
                        </div>
                        <div>
                            <label class="news-label">Foto Bloco Livre</label>
                            <input type="text" id="editFotoBloco" class="news-input" placeholder="caminho/relativo.jpg">
                            <small style="color: #9ca3af;">Alt: <input type="text" id="editAltLivre" style="width: 100%; margin-top: 5px;"></small>
                        </div>
                    </div>

                    <hr style="margin: 30px 0; border-color: var(--news-border-color);">

                    <!-- Conteúdo -->
                    <h4 style="margin: 20px 0 15px; color: #5a67d8;"><i class="fas fa-align-left"></i> Conteúdo</h4>

                    <label class="news-label"><i class="fas fa-file-alt"></i> Campo Livre <small style="color: #9ca3af;">(obrigatório para o novo layout)</small></label>
                    <textarea id="editBlocoLivre" class="news-input" rows="15" placeholder="Conteúdo HTML do campo livre..."></textarea>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                        <div>
                            <label class="news-label"><i class="fas fa-bullhorn"></i> Chamada 1º Bloco <small style="color: #9ca3af;">(somente para o novo layout)</small></label>
                            <input type="text" id="editChamada1Bloco" class="news-input" placeholder="Título do primeiro bloco">
                        </div>
                        <div>
                            <label class="news-label"><i class="fas fa-bullhorn"></i> Chamada 2º Bloco <small style="color: #9ca3af;">(somente para o novo layout)</small></label>
                            <input type="text" id="editChamadaBloco" class="news-input" placeholder="Título do segundo bloco">
                        </div>
                    </div>

                    <label class="news-label" style="margin-top: 20px;"><i class="fas fa-box-open"></i> Campo "MORE PRODUCTS" <small style="color: #9ca3af;">(somente para o novo layout)</small></label>
                    <input type="text" id="editMoreProducts" class="news-input" placeholder="HTML do campo MORE PRODUCTS">

                    <!-- Destaques -->
                    <h4 style="margin: 30px 0 15px; color: #5a67d8;"><i class="fas fa-star"></i> Destaques</h4>
                    <div id="newsDestaquesContainer"></div>
                    <div style="margin-top: 15px; text-align: center;">
                        <button type="button" onclick="newsAdicionarDestaque()" class="news-btn-modern news-btn-info-modern" style="font-size: 0.9rem; padding: 8px 16px;">
                            <i class="fas fa-plus"></i> Adicionar Destaque
                        </button>
                    </div>
                </form>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--news-border-color);">
                <button type="button" onclick="newsFecharModalEdit()" class="news-btn-modern news-btn-secondary-modern">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" onclick="newsSalvarEdicao()" class="news-btn-modern news-btn-primary-modern">
                    <i class="fas fa-save"></i> Salvar Alterações
                </button>
            </div>
        </div>
    </div>
    <!-- Header -->
    <div class="news-admin-header">
        <h1 style="color:#fff; font-weight:bold;"><i class="fas fa-newspaper"></i> Newsletters</h1>
        <p>Sistema de Administração de Newsletters e Comunicações</p>
    </div>

    <!-- Filtros e Ações -->
    <div class="news-filters-section">
        <!-- Linha 1: Busca e Ações -->
        <div class="news-filter-group">
            <div class="news-search-box">
                <input type="text" id="newsFilterNome" placeholder="Buscar por nome da newsletter..." onkeyup="newsLoadData()">
                <i class="fas fa-search"></i>
            </div>
            <?php if (!$readOnly): ?>
                <button onclick="newsNovoItem()" class="news-btn-modern news-btn-primary-modern">
                    <i class="fas fa-plus"></i> Nova Newsletter
                </button>
            <?php endif; ?>
            <button onclick="newsLoadData()" class="news-btn-modern news-btn-secondary-modern">
                <i class="fas fa-sync-alt"></i> Atualizar
            </button>
            <a href="#" onclick="newsModoConsulta(); return false;" class="news-btn-modern news-btn-info-modern">
                <i class="fas fa-eye"></i> Modo Consulta
            </a>
        </div>

        <!-- Linha 2: Filtros de Status -->
        <div class="news-filter-status-row">
            <div class="news-filter-status-label">
                <i class="fas fa-filter"></i> Filtrar por Status:
            </div>

            <div class="news-filter-status-group">
                <div class="news-status-filter-item">
                    <label for="filterAtivoWeb">
                        <i class="fas fa-globe"></i> Web:
                    </label>
                    <select id="filterAtivoWeb" class="news-status-select" onchange="newsLoadData()">
                        <option value="all">Todos</option>
                        <option value="true">Ativo</option>
                        <option value="false">Inativo</option>
                    </select>
                </div>

                <div class="news-status-filter-item">
                    <label for="filterAtivoHome">
                        <i class="fas fa-home"></i> Home:
                    </label>
                    <select id="filterAtivoHome" class="news-status-select" onchange="newsLoadData()">
                        <option value="all">Todos</option>
                        <option value="true">Ativo</option>
                        <option value="false">Inativo</option>
                    </select>
                </div>

                <div class="news-status-filter-item">
                    <label for="filterAtivoPassion">
                        <i class="fas fa-heart"></i> Passion:
                    </label>
                    <select id="filterAtivoPassion" class="news-status-select" onchange="newsLoadData()">
                        <option value="all">Todos</option>
                        <option value="true">Ativo</option>
                        <option value="false">Inativo</option>
                    </select>
                </div>

                <div class="news-status-filter-item">
                    <label for="filterAtivoBe">
                        <i class="fas fa-building"></i> BE:
                    </label>
                    <select id="filterAtivoBe" class="news-status-select" onchange="newsLoadData()">
                        <option value="all">Todos</option>
                        <option value="true">Ativo</option>
                        <option value="false">Inativo</option>
                    </select>
                </div>

                <div class="news-status-filter-item">
                    <label for="filterEmpresa">
                        <i class="fas fa-briefcase"></i> Empresa:
                    </label>
                    <select id="filterEmpresa" class="news-status-select" onchange="newsLoadData()">
                        <option value="all">Todas</option>
                        <option value="1">Blumar</option>
                        <option value="2">Rio Life</option>
                        <option value="3">Eventos</option>
                        <option value="4">BeBrazil</option>
                    </select>
                </div>

                <button onclick="newsLimparFiltros()" class="news-btn-clear-filters" title="Limpar todos os filtros">
                    <i class="fas fa-times-circle"></i> Limpar
                </button>
            </div>
        </div>
    </div>

    <!-- Loading -->
    <div class="news-loading-spinner" id="newsLoadingSpinner">
        <div class="news-spinner"></div>
        <p style="margin-top: 15px; color: rgba(255,255,255,0.7);">Carregando...</p>
    </div>

    <!-- Tabela -->
    <div class="news-table-container">
        <table class="news-table-modern">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Data</th>
                    <th>Empresa</th>
                    <th>Status Web</th>
                    <th>Status Home</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="newsTabelaBody">
                <!-- Dados carregados via JS -->
            </tbody>
        </table>

        <!-- Paginação -->
        <div class="news-pagination-container">
            <div class="news-pagination-info" id="newsPaginationInfo">
                Mostrando 0 - 0 de 0 registros
            </div>
            <ul class="news-pagination" id="newsPaginationButtons">
                <!-- Botões gerados via JS -->
            </ul>
        </div>
    </div>

    <!-- Modal Visualizar -->
    <div id="newsModalView" class="news-modal-overlay">
        <div class="news-modal-content-modern">
            <div class="news-modal-header-modern">
                <h3><i class="fas fa-eye"></i> Visualizar Newsletter</h3>
                <button onclick="newsFecharModalView()" style="position: absolute; top: 10px; right: 10px; background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <div id="newsViewContent"></div>
            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--news-border-color);">
                <button type="button" onclick="newsFecharModalView()" class="news-btn-modern news-btn-secondary-modern">
                    <i class="fas fa-times"></i> Fechar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Inserir Nova Newsletter -->
    <div id="newsModalInsert" class="news-modal-overlay">
        <div class="news-modal-content-modern" style="max-width: 1200px; max-height: 90vh; overflow-y: auto;">
            <div class="news-modal-header-modern">
                <h3><i class="fas fa-plus-circle"></i> Nova Newsletter</h3>
                <button onclick="newsFecharModalInsert()" style="position: absolute; top: 10px; right: 10px; background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <div id="newsInsertContent" style="padding-right: 10px;">
                <form id="newsInsertForm">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <!-- Coluna 1 -->
                        <div>
                            <label class="news-label"><i class="fas fa-tag"></i> Nome da Newsletter *</label>
                            <input type="text" id="insertNome" class="news-input" required>

                            <label class="news-label"><i class="fas fa-calendar"></i> Data Extenso</label>
                            <input type="text" id="insertDataExtenso" class="news-input" placeholder="Ex: Janeiro 2025">

                            <label class="news-label"><i class="fas fa-heading"></i> Título</label>
                            <input type="text" id="insertTitulo" class="news-input">

                            <label class="news-label"><i class="fas fa-file-pdf"></i> PDF <small style="color: #9ca3af;">(salvo em: /novo_site/admin/news/pdf)</small></label>
                            <input type="text" id="insertPdf" class="news-input" placeholder="nome_arquivo.pdf">

                            <label class="news-label"><i class="fas fa-building"></i> Empresa</label>
                            <div style="display: flex; flex-direction: column; gap: 8px; background: rgba(255, 255, 255, 0.05); padding: 15px; border-radius: 10px;">
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="radio" name="insertEmpresa" id="insertEmpresa1" value="1" checked>
                                    <span>Blumar</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="radio" name="insertEmpresa" id="insertEmpresa2" value="2">
                                    <span>Rio Life</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="radio" name="insertEmpresa" id="insertEmpresa3" value="3">
                                    <span>Eventos</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="radio" name="insertEmpresa" id="insertEmpresa4" value="4">
                                    <span>BeBrazil</span>
                                </label>
                            </div>

                            <label class="news-label"><i class="fas fa-palette"></i> Cor PE (hex sem #)</label>
                            <input type="text" id="insertCorPe" class="news-input" placeholder="F9020E" value="F9020E">
                        </div>

                        <!-- Coluna 2 - Status -->
                        <div>
                            <label class="news-label"><i class="fas fa-toggle-on"></i> Status de Exibição</label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                <label><input type="checkbox" id="insertAtivoWeb" checked> Ativo Web</label>
                                <label><input type="checkbox" id="insertAtivoHome"> Ativo Home</label>
                                <label><input type="checkbox" id="insertAtivoPassion"> Ativo Passion</label>
                                <label><input type="checkbox" id="insertAtivoBe"> Ativo BE</label>
                                <label><input type="checkbox" id="insertTituloAtivo"> Título Ativo</label>
                                <label><input type="checkbox" id="insertRecep"> Recep</label>
                                <label><input type="checkbox" id="insertHeaderItaliano"> Topo Italiano</label>
                                <label><input type="checkbox" id="insertNovoLayout" checked> Novo Layout</label>
                            </div>
                        </div>
                    </div>

                    <hr style="margin: 30px 0; border-color: var(--news-border-color);">

                    <!-- Imagens -->
                    <h4 style="margin: 20px 0 15px; color: #5a67d8;"><i class="fas fa-images"></i> Imagens</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <div>
                            <label class="news-label">Imagem Topo</label>
                            <input type="text" id="insertImgTopo" class="news-input" placeholder="caminho/relativo.jpg">
                            <small style="color: #9ca3af;">Alt: <input type="text" id="insertAltTopo" style="width: 100%; margin-top: 5px;"></small>
                        </div>
                        <div>
                            <label class="news-label">Foto Bloco Livre</label>
                            <input type="text" id="insertFotoBloco" class="news-input" placeholder="caminho/relativo.jpg">
                            <small style="color: #9ca3af;">Alt: <input type="text" id="insertAltLivre" style="width: 100%; margin-top: 5px;"></small>
                        </div>
                    </div>

                    <hr style="margin: 30px 0; border-color: var(--news-border-color);">

                    <!-- Conteúdo -->
                    <h4 style="margin: 20px 0 15px; color: #5a67d8;"><i class="fas fa-align-left"></i> Conteúdo</h4>

                    <label class="news-label"><i class="fas fa-file-alt"></i> Campo Livre <small style="color: #9ca3af;">(obrigatório para o novo layout)</small></label>
                    <textarea id="insertBlocoLivre" class="news-input" rows="15" placeholder="Conteúdo HTML do campo livre..."></textarea>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                        <div>
                            <label class="news-label"><i class="fas fa-bullhorn"></i> Chamada 1º Bloco <small style="color: #9ca3af;">(somente para o novo layout)</small></label>
                            <input type="text" id="insertChamada1Bloco" class="news-input" placeholder="Título do primeiro bloco">
                        </div>
                        <div>
                            <label class="news-label"><i class="fas fa-bullhorn"></i> Chamada 2º Bloco <small style="color: #9ca3af;">(somente para o novo layout)</small></label>
                            <input type="text" id="insertChamadaBloco" class="news-input" placeholder="Título do segundo bloco">
                        </div>
                    </div>

                    <label class="news-label" style="margin-top: 20px;"><i class="fas fa-box-open"></i> Campo "MORE PRODUCTS" <small style="color: #9ca3af;">(somente para o novo layout)</small></label>
                    <input type="text" id="insertMoreProducts" class="news-input" placeholder="HTML do campo MORE PRODUCTS" value='MORE <b>PRODUCTS</b><br><font size=1>Follow below some other products that might interest you!</font>'>
                </form>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--news-border-color);">
                <button type="button" onclick="newsFecharModalInsert()" class="news-btn-modern news-btn-secondary-modern">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" onclick="newsSalvarInsercao()" class="news-btn-modern news-btn-primary-modern">
                    <i class="fas fa-save"></i> Criar Newsletter
                </button>
            </div>
        </div>
    </div>
</div>

<script src="newsv2/listar.js?v=1.1">
</script>
