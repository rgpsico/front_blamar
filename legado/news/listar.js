(function () {
    // Encapsulamento de variáveis e funções
    const NEWS_API_URL = 'api/newsletters.php';
    const NEWS_READ_ONLY = $('#readOnly').val();
    let newsCurrentPage = 1;
    let newsItemsPerPage = 10;
    let newsTotalItems = 0;
    let newsAllData = [];

    // Carrega a listagem com paginação
    window.newsLoadData = function (page = 1) {
        newsCurrentPage = page;
        const filtroNome = document.getElementById('newsFilterNome').value;
        const filtroAtivoWeb = document.getElementById('filterAtivoWeb') ? document.getElementById('filterAtivoWeb').value : 'all';
        const filtroAtivoHome = document.getElementById('filterAtivoHome') ? document.getElementById('filterAtivoHome').value : 'all';
        const filtroAtivoPassion = document.getElementById('filterAtivoPassion') ? document.getElementById('filterAtivoPassion').value : 'all';
        const filtroAtivoBe = document.getElementById('filterAtivoBe') ? document.getElementById('filterAtivoBe').value : 'all';
        const filtroEmpresa = document.getElementById('filterEmpresa') ? document.getElementById('filterEmpresa').value : 'all';

        // Mostra loading
        document.getElementById('newsLoadingSpinner').style.display = 'block';
        document.querySelector('.news-table-container').style.opacity = '0.5';

        let url = `${NEWS_API_URL}?request=listar_news&limit=1000`;
        if (filtroNome) {
            url += `&filtro_nome=${encodeURIComponent(filtroNome)}`;
        }
        if (filtroAtivoWeb !== 'all') {
            url += `&filtro_ativo_web=${filtroAtivoWeb}`;
        }
        if (filtroAtivoHome !== 'all') {
            url += `&filtro_ativo_home=${filtroAtivoHome}`;
        }
        if (filtroAtivoPassion !== 'all') {
            url += `&filtro_ativo_passion=${filtroAtivoPassion}`;
        }
        if (filtroAtivoBe !== 'all') {
            url += `&filtro_ativo_be=${filtroAtivoBe}`;
        }
        if (filtroEmpresa !== 'all') {
            url += `&filtro_empresa=${filtroEmpresa}`;
        }

        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json',
            error: function () {
                alert('Erro ao carregar listagem de Newsletters!');
                document.getElementById('newsLoadingSpinner').style.display = 'none';
                document.querySelector('.news-table-container').style.opacity = '1';
            },
            success: function (data) {
                newsAllData = data;
                newsTotalItems = data.length;
                newsRenderTable();
                newsRenderPagination();
                document.getElementById('newsLoadingSpinner').style.display = 'none';
                document.querySelector('.news-table-container').style.opacity = '1';
            }
        });
    };

    // Função auxiliar para converter número da empresa em nome
    function newsGetEmpresaNome(empresaId) {
        const empresas = {
            '1': 'Blumar',
            '2': 'Rio Life',
            '3': 'Eventos',
            '4': 'BeBrazil'
        };
        return empresas[String(empresaId)] || empresaId || '-';
    }

    // Renderiza a tabela
    function newsRenderTable() {
        const tbody = document.getElementById('newsTabelaBody');
        tbody.innerHTML = '';

        const startIndex = (newsCurrentPage - 1) * newsItemsPerPage;
        const endIndex = Math.min(startIndex + newsItemsPerPage, newsTotalItems);
        const pageData = newsAllData.slice(startIndex, endIndex);

        if (pageData.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: rgba(255,255,255,0.5);">
                        <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 15px; display: block;"></i>
                        Nenhum registro encontrado
                    </td>
                </tr>
            `;
            return;
        }

        pageData.forEach(function (news) {
            const isActiveWeb = news.ativo_web === true || news.ativo_web === 't';
            const isActiveHome = news.ativo_home === true || news.ativo_home === 't';

            const row = document.createElement('tr');
            row.style.animation = 'news-fadeIn 0.5s ease';
            row.innerHTML = `
                <td><strong>#${news.pk_news || news.id}</strong></td>
                <td>${news.nome || '-'}</td>
                <td>${news.data_formatada || news.data || '-'}</td>
                <td>${newsGetEmpresaNome(news.empresa)}</td>
                <td><span class="news-badge-active ${isActiveWeb ? 'news-active' : 'news-inactive'}">${isActiveWeb ? 'Ativo' : 'Inativo'}</span></td>
                <td><span class="news-badge-active ${isActiveHome ? 'news-active' : 'news-inactive'}">${isActiveHome ? 'Ativo' : 'Inativo'}</span></td>
                <td>
                    <div class="news-action-buttons">
                        <button onclick="newsVerItem(${news.pk_news || news.id})" class="news-btn-action news-btn-view">
                            <i class="fas fa-eye"></i> Ver
                        </button>
                        <button onclick="newsVisualizarSites(${news.pk_news || news.id})" class="news-btn-action news-btn-info" title="Visualizar nos sites">
                            <i class="fas fa-external-link-alt"></i> Sites
                        </button>
                        ${!NEWS_READ_ONLY ? `
                        <button onclick="newsEditarItem(${news.pk_news || news.id})" class="news-btn-action news-btn-edit">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                        <button onclick="newsDuplicarItem(${news.pk_news || news.id})" class="news-btn-action news-btn-warning" title="Duplicar Newsletter">
                            <i class="fas fa-copy"></i> Duplicar
                        </button>
                        <button onclick="newsExcluirItem(${news.pk_news || news.id})" class="news-btn-action news-btn-delete">
                            <i class="fas fa-trash"></i> Excluir
                        </button>
                        ` : ''}
                    </div>
                </td>
            `;
            tbody.appendChild(row);
        });

        newsUpdatePaginationInfo(startIndex + 1, endIndex);
    }

    // Renderiza paginação
    function newsRenderPagination() {
        const totalPages = Math.ceil(newsTotalItems / newsItemsPerPage);
        const paginationButtons = document.getElementById('newsPaginationButtons');
        paginationButtons.innerHTML = '';

        if (totalPages <= 1) return;

        // Botão Anterior
        const prevLi = document.createElement('li');
        const prevBtn = document.createElement('button');
        prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
        prevBtn.disabled = newsCurrentPage === 1;
        prevBtn.onclick = () => newsChangePage(newsCurrentPage - 1);
        prevLi.appendChild(prevBtn);
        paginationButtons.appendChild(prevLi);

        // Botões de páginas
        let startPage = Math.max(1, newsCurrentPage - 2);
        let endPage = Math.min(totalPages, newsCurrentPage + 2);

        if (startPage > 1) {
            const li = document.createElement('li');
            const btn = document.createElement('button');
            btn.textContent = '1';
            btn.onclick = () => newsChangePage(1);
            li.appendChild(btn);
            paginationButtons.appendChild(li);

            if (startPage > 2) {
                const li = document.createElement('li');
                const btn = document.createElement('button');
                btn.textContent = '...';
                btn.disabled = true;
                li.appendChild(btn);
                paginationButtons.appendChild(li);
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            const li = document.createElement('li');
            const btn = document.createElement('button');
            btn.textContent = i;
            if (i === newsCurrentPage) btn.classList.add('news-active');
            btn.onclick = () => newsChangePage(i);
            li.appendChild(btn);
            paginationButtons.appendChild(li);
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                const li = document.createElement('li');
                const btn = document.createElement('button');
                btn.textContent = '...';
                btn.disabled = true;
                li.appendChild(btn);
                paginationButtons.appendChild(li);
            }
            const li = document.createElement('li');
            const btn = document.createElement('button');
            btn.textContent = totalPages;
            btn.onclick = () => newsChangePage(totalPages);
            li.appendChild(btn);
            paginationButtons.appendChild(li);
        }

        // Botão Próximo
        const nextLi = document.createElement('li');
        const nextBtn = document.createElement('button');
        nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
        nextBtn.disabled = newsCurrentPage === totalPages;
        nextBtn.onclick = () => newsChangePage(newsCurrentPage + 1);
        nextLi.appendChild(nextBtn);
        paginationButtons.appendChild(nextLi);
    }

    // Muda de página
    function newsChangePage(page) {
        if (page < 1 || page > Math.ceil(newsTotalItems / newsItemsPerPage)) return;
        newsCurrentPage = page;
        newsRenderTable();
        newsRenderPagination();
        document.querySelector('.news-table-container').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
    window.newsChangePage = newsChangePage;

    // Atualiza info de paginação
    function newsUpdatePaginationInfo(start, end) {
        document.getElementById('newsPaginationInfo').textContent =
            `Mostrando ${start} - ${end} de ${newsTotalItems} registros`;
    }

    // Novo item
    window.newsNovoItem = function () {
        // Limpar todos os campos do formulário
        document.getElementById('insertNome').value = '';
        document.getElementById('insertDataExtenso').value = '';
        document.getElementById('insertTitulo').value = '';
        document.getElementById('insertPdf').value = '';
        document.getElementById('insertBlocoLivre').value = '';
        document.getElementById('insertChamada1Bloco').value = '';
        document.getElementById('insertChamadaBloco').value = '';
        document.getElementById('insertMoreProducts').value = 'MORE <b>PRODUCTS</b><br><font size=1>Follow below some other products that might interest you!</font>';
        document.getElementById('insertCorPe').value = 'F9020E';
        document.getElementById('insertImgTopo').value = '';
        document.getElementById('insertAltTopo').value = '';
        document.getElementById('insertFotoBloco').value = '';
        document.getElementById('insertAltLivre').value = '';

        // Resetar empresa para Blumar (valor 1)
        document.getElementById('insertEmpresa1').checked = true;

        // Resetar checkboxes - padrão: Ativo Web e Novo Layout marcados
        document.getElementById('insertAtivoWeb').checked = true;
        document.getElementById('insertAtivoHome').checked = false;
        document.getElementById('insertAtivoPassion').checked = false;
        document.getElementById('insertAtivoBe').checked = false;
        document.getElementById('insertTituloAtivo').checked = false;
        document.getElementById('insertRecep').checked = false;
        document.getElementById('insertHeaderItaliano').checked = false;
        document.getElementById('insertNovoLayout').checked = true;

        // Abrir modal
        document.getElementById('newsModalInsert').style.display = 'block';
    };

    // Editar item
    window.newsEditarItem = function (id) {
        alert('Funcionalidade de edição em desenvolvimento. Use os formulários existentes.');
    };

    // Excluir item
    window.newsExcluirItem = function (id) {
        if (!confirm('Confirma a exclusão desta Newsletter? Esta ação não pode ser desfeita.')) return;

        const url = `${NEWS_API_URL}?request=excluir_news&id=${id}`;
        $.ajax({
            url: url,
            method: 'DELETE',
            dataType: 'json',
            error: function () {
                alert('Erro ao excluir Newsletter!');
            },
            success: function (response) {
                if (response.success) {
                    alert('Newsletter excluída com sucesso!');
                    newsLoadData(newsCurrentPage);
                } else {
                    alert('Erro: ' + (response.error || 'Falha na exclusão'));
                }
            }
        });
    };

    // Ver item
    window.newsVerItem = function (id) {
        const url = `${NEWS_API_URL}?request=buscar_news&id=${id}`;
        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json',
            error: function () {
                alert('Erro ao carregar visualização!');
            },
            success: function (data) {
                const isActiveWeb = data.ativo_web === true || data.ativo_web === 't';
                const isActiveHome = data.ativo_home === true || data.ativo_home === 't';
                const isActivePassion = data.ativo_passion === true || data.ativo_passion === 't';
                const isActiveBe = data.ativo_be === true || data.ativo_be === 't';

                let imagensHtml = '';
                if (data.imagens && data.imagens.length > 0) {
                    imagensHtml = data.imagens.map(img =>
                        `<div style="margin-bottom: 10px;">
                                <p style="margin: 5px 0; color: rgba(255,255,255,0.7); font-size: 0.85rem;">${img.field}</p>
                                <img src="${img.image_url}" alt="${img.alt_text}" style="max-width: 200px; max-height: 150px; border-radius: 8px;">
                            </div>`
                    ).join('');
                } else {
                    imagensHtml = '<p style="color: rgba(255,255,255,0.5);">Nenhuma imagem</p>';
                }

                let destaquesHtml = '';
                if (data.destaques && data.destaques.length > 0) {
                    destaquesHtml = data.destaques.map(dest =>
                        `<div style="padding: 15px; background: rgba(255,255,255,0.03); border-radius: 12px; margin-bottom: 10px;">
                                <h4 style="margin: 0 0 10px 0; color: #5a67d8;">${dest.titulo || '-'}</h4>
                                <p style="margin: 5px 0; font-size: 0.85rem;"><strong>Ordem:</strong> ${dest.ordem || dest.dia_conteudo}</p>
                                <p style="margin: 5px 0; font-size: 0.85rem;"><strong>Subtítulo:</strong> ${dest.subtitulo || '-'}</p>
                                <p style="margin: 5px 0; font-size: 0.85rem;"><strong>Layout:</strong> ${dest.layout || dest.layout_news}</p>
                            </div>`
                    ).join('');
                } else {
                    destaquesHtml = '<p style="color: rgba(255,255,255,0.5);">Nenhum destaque cadastrado</p>';
                }

                let content = `
                        <div style="padding: 10px 0;">
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; margin-bottom: 8px; color: rgba(255,255,255,0.9); font-weight: 600; font-size: 0.9rem;"><i class="fas fa-hashtag"></i> ID</label>
                                <div style="padding: 12px; background: rgba(255,255,255,0.05); border-radius: 8px;">
                                    #${data.pk_news || data.id}
                                </div>
                            </div>
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; margin-bottom: 8px; color: rgba(255,255,255,0.9); font-weight: 600; font-size: 0.9rem;"><i class="fas fa-tag"></i> Nome</label>
                                <div style="padding: 12px; background: rgba(255,255,255,0.05); border-radius: 8px;">
                                    ${data.nome}
                                </div>
                            </div>
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; margin-bottom: 8px; color: rgba(255,255,255,0.9); font-weight: 600; font-size: 0.9rem;"><i class="fas fa-calendar"></i> Data</label>
                                <div style="padding: 12px; background: rgba(255,255,255,0.05); border-radius: 8px;">
                                    ${data.data_formatada || data.data || '-'}
                                </div>
                            </div>
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; margin-bottom: 8px; color: rgba(255,255,255,0.9); font-weight: 600; font-size: 0.9rem;"><i class="fas fa-heading"></i> Título</label>
                                <div style="padding: 12px; background: rgba(255,255,255,0.05); border-radius: 8px;">
                                    ${data.titulo || '-'}
                                </div>
                            </div>
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; margin-bottom: 8px; color: rgba(255,255,255,0.9); font-weight: 600; font-size: 0.9rem;"><i class="fas fa-building"></i> Empresa</label>
                                <div style="padding: 12px; background: rgba(255,255,255,0.05); border-radius: 8px;">
                                    ${data.empresa || '-'}
                                </div>
                            </div>
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; margin-bottom: 8px; color: rgba(255,255,255,0.9); font-weight: 600; font-size: 0.9rem;"><i class="fas fa-toggle-on"></i> Status</label>
                                <div style="padding: 12px; display: flex; gap: 10px; flex-wrap: wrap;">
                                    <span class="news-badge-active ${isActiveWeb ? 'news-active' : 'news-inactive'}">
                                        Web: ${isActiveWeb ? 'Ativo' : 'Inativo'}
                                    </span>
                                    <span class="news-badge-active ${isActiveHome ? 'news-active' : 'news-inactive'}">
                                        Home: ${isActiveHome ? 'Ativo' : 'Inativo'}
                                    </span>
                                    <span class="news-badge-active ${isActivePassion ? 'news-active' : 'news-inactive'}">
                                        Passion: ${isActivePassion ? 'Ativo' : 'Inativo'}
                                    </span>
                                    <span class="news-badge-active ${isActiveBe ? 'news-active' : 'news-inactive'}">
                                        BE: ${isActiveBe ? 'Ativo' : 'Inativo'}
                                    </span>
                                </div>
                            </div>
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; margin-bottom: 8px; color: rgba(255,255,255,0.9); font-weight: 600; font-size: 0.9rem;"><i class="fas fa-images"></i> Imagens</label>
                                <div style="padding: 12px; background: rgba(255,255,255,0.05); border-radius: 8px; display: flex; gap: 15px; flex-wrap: wrap;">
                                    ${imagensHtml}
                                </div>
                            </div>
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; margin-bottom: 8px; color: rgba(255,255,255,0.9); font-weight: 600; font-size: 0.9rem;"><i class="fas fa-star"></i> Destaques (${data.destaques ? data.destaques.length : 0})</label>
                                <div style="padding: 12px; background: rgba(255,255,255,0.05); border-radius: 8px;">
                                    ${destaquesHtml}
                                </div>
                            </div>
                        </div>
                    `;
                document.getElementById('newsViewContent').innerHTML = content;
                document.getElementById('newsModalView').style.display = 'block';
            }
        });
    };

    // Fechar modal
    window.newsFecharModalView = function () {
        document.getElementById('newsModalView').style.display = 'none';
    };

    // Modo consulta
    window.newsModoConsulta = function () {
        alert('Modo consulta ativado - funcionalidades de edição foram ocultadas.');
    };

    // Limpar todos os filtros
    window.newsLimparFiltros = function () {
        document.getElementById('newsFilterNome').value = '';
        if (document.getElementById('filterAtivoWeb')) {
            document.getElementById('filterAtivoWeb').value = 'all';
        }
        if (document.getElementById('filterAtivoHome')) {
            document.getElementById('filterAtivoHome').value = 'all';
        }
        if (document.getElementById('filterAtivoPassion')) {
            document.getElementById('filterAtivoPassion').value = 'all';
        }
        if (document.getElementById('filterAtivoBe')) {
            document.getElementById('filterAtivoBe').value = 'all';
        }
        if (document.getElementById('filterEmpresa')) {
            document.getElementById('filterEmpresa').value = 'all';
        }
        newsLoadData();
    };

    // Fechar modal ao clicar fora
    window.onclick = function (event) {
        const modalView = document.getElementById('newsModalView');
        if (event.target === modalView) {
            newsFecharModalView();
        }
    };

    // Inicialização
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            newsLoadData();
        });
    } else {
        newsLoadData();
    }
})();

// === EDITAR ITEM ===
window.newsEditarItem = function (id) {
    const url = `api/newsletters.php?request=buscar_news_completa&id=${id}`;

    $.ajax({
        url: url,
        method: 'GET',
        dataType: 'json',
        success: function (data) {
            // Preencher campos principais
            document.getElementById('editNewsId').value = data.pk_news || data.id || id;
            document.getElementById('editNome').value = data.nome || '';
            document.getElementById('editDataExtenso').value = data.data_extenso || '';
            document.getElementById('editTitulo').value = data.titulo || '';

            // Selecionar empresa correta
            const empresaValue = data.empresa || '1';
            const empresaRadio = document.querySelector(`input[name="editEmpresa"][value="${empresaValue}"]`);
            if (empresaRadio) {
                empresaRadio.checked = true;
            }

            // Novos campos
            document.getElementById('editPdf').value = data.pdf || '';
            document.getElementById('editBlocoLivre').value = data.bloco_livre || '';
            document.getElementById('editChamada1Bloco').value = data.chamada1_bloco || '';
            document.getElementById('editChamadaBloco').value = data.chamada_bloco || '';
            document.getElementById('editMoreProducts').value = data.more_poducts || '';

            // Imagens
            document.getElementById('editImgTopo').value = data.img_topo || '';
            document.getElementById('editAltTopo').value = data.alt_topo || '';
            document.getElementById('editFotoBloco').value = data.foto_bloco || '';
            document.getElementById('editAltLivre').value = data.alt_livre || '';
            document.getElementById('editCorPe').value = data.cor_pe || 'F9020E';

            // Checkboxes de status (tratando valores booleanos e 't'/'f')
            document.getElementById('editAtivoWeb').checked = data.ativo_web === true || data.ativo_web === 't';
            document.getElementById('editAtivoHome').checked = data.ativo_home === true || data.ativo_home === 't';
            document.getElementById('editAtivoPassion').checked = data.ativo_passion === true || data.ativo_passion === 't';
            document.getElementById('editAtivoBe').checked = data.ativo_be === true || data.ativo_be === 't';
            document.getElementById('editTituloAtivo').checked = data.titulo_ativo === true || data.titulo_ativo === 't';
            document.getElementById('editRecep').checked = data.recep === true || data.recep === 't';
            document.getElementById('editHeaderItaliano').checked = data.is_header_italiano === true || data.is_header_italiano === 't';
            document.getElementById('editNovoLayout').checked = data.novo_layout === true || data.novo_layout === 't';

            // Renderizar destaques
            const container = document.getElementById('newsDestaquesContainer');
            container.innerHTML = '';
            if (data.destaques && data.destaques.length > 0) {
                data.destaques.forEach((dest, index) => newsCriarCardDestaque(dest, index));
            } else {
                container.innerHTML = '<p style="color: #9ca3af; text-align:center; padding: 30px;">Nenhum destaque cadastrado</p>';
            }

            document.getElementById('newsModalEdit').style.display = 'block';
        },
        error: function (xhr, status, error) {
            console.error('Erro ao carregar dados:', xhr.responseText);
            alert('Erro ao carregar dados para edição! Verifique o console para mais detalhes.');
        }
    });
};

// Cria card de destaque editável
function newsCriarCardDestaque(destaque = {}, index) {
    console.log(destaque)
    const container = document.getElementById('newsDestaquesContainer');
    if (!container) return;

    const card = document.createElement('div');
    card.className = 'destaque-card';
    card.innerHTML = `
      <button type="button" class="remove-destaque teste" onclick="newsRemoverDestaque(this, ${destaque.id})">×</button>
        <div class="destaque-header">
            <strong>Destaque ${index + 1}</strong>
            <span>Ordem:</span>
            <input type="number" class="ordem-destaque" value="${destaque.ordem || destaque.dia_conteudo || (index + 1)}" min="1" max="30">
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div>
                <label class="news-label">Título</label>
                <input type="text" class="titulo-destaque news-input" value="${destaque.titulo || destaque.titulo_news || ''}">
            </div>
            <div style="display: flex; align-items: flex-end; gap: 8px;">
                <label style="display: flex; align-items: center; gap: 8px; margin: 0;">
                    <input type="checkbox" class="titulo-ativo-destaque" ${destaque.titulo_ativo ? 'checked' : ''}>
                    <span style="font-size: 0.9rem;">Título Oculto</span>
                </label>
            </div>
            <div>
                <label class="news-label">Subtítulo</label>
                <input type="text" class="subtitulo-destaque news-input" value="${destaque.subtitulo || destaque.sub_titulo_news || ''}">
            </div>
            <div>
                <label class="news-label">Foto de Link</label>
                <input type="text" class="img-link-destaque news-input" value="${destaque.img_link || ''}">
            </div>
        </div>

        <div style="margin-top: 15px;">
            <label class="news-label">Descritivo News Reduzida <small style="color: #9ca3af;">(máx 170 caracteres)</small></label>
            <textarea class="link-endereco-destaque news-input" rows="3" maxlength="170">${destaque.link_endereco || ''}</textarea>
        </div>

        <div style="display: flex; align-items: center; gap: 8px; margin-top: 10px;">
            <label style="display: flex; align-items: center; gap: 8px; margin: 0;">
                <input type="checkbox" class="link-ativo-destaque" ${destaque.link_ativo ? 'checked' : ''}>
                <span style="font-size: 0.9rem;">Link Ativo</span>
            </label>
        </div>

        <div style="margin-top: 15px;">
            <label class="news-label">Descritivo</label>
            <textarea class="descritivo-conteudo-destaque news-input" rows="10">${destaque.descricao || ''}</textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
            <div>
                <label class="news-label">Foto (Imagem Principal)</label>
                <input type="text" class="img-destaque news-input" value="${destaque.imagem || destaque.img1_conteudo || ''}">
            </div>
            <div>
                <label class="news-label">Foto para Reduzida</label>
                <input type="text" class="img-reduz-destaque news-input" value="${destaque.imagem_reduzida || ''}">
            </div>
            <div>
                <label class="news-label">Texto Alternativo (Alt)</label>
                <input type="text" class="alt-destaque news-input" value="${destaque.alt || ''}">
            </div>
            <div>
                <label class="news-label">Layout</label>
                <select class="layout-destaque news-input" style='color:#fff; fontw-eight:bold; background:#242a42;'>
                    <option value="1" ${(destaque.layout === '1' || destaque.layout_news === '1') ? 'selected' : ''}>Imagem à esquerda</option>
                    <option value="2" ${(destaque.layout === '2' || destaque.layout_news === '2') ? 'selected' : ''}>Não incluir imagem</option>
                    <option value="3" ${(destaque.layout === '3' || destaque.layout_news === '3') ? 'selected' : ''}>Imagem à direita</option>
                    <option value="4" ${(destaque.layout === '4' || destaque.layout_news === '4') ? 'selected' : ''}>Somente imagem</option>
                    <option value="5" ${(destaque.layout === '5' || destaque.layout_news === '5') ? 'selected' : ''}>Foto topo</option>
                </select>
            </div>
        </div>
        <div style="margin-top: 15px; display: flex; gap: 20px; flex-wrap: wrap;">
            <label><input type="checkbox" class="exibir-destaque" ${destaque.exibir !== false && destaque.exibe_destaque !== false ? 'checked' : ''}> Exibir este destaque</label>
            <label><input type="checkbox" class="expert-destaque" ${(destaque.especialista && destaque.especialista !== '0') || (destaque.expert && destaque.expert !== '0') ? 'checked' : ''}> É Comentário de Especialista</label>
        </div>

        <div class="especialista-section" style="margin-top: 15px; padding: 15px; background: rgba(90, 103, 216, 0.1); border: 1px solid rgba(90, 103, 216, 0.3); border-radius: 8px; ${(destaque.especialista && destaque.especialista !== '0') || (destaque.expert && destaque.expert !== '0') ? '' : 'display: none;'}">
            <label class="news-label"><i class="fas fa-user-tie"></i> Selecionar Especialista</label>
            <select class="especialista-select news-input"  style="color:#fff; fontw-eight:bold; background:#242a42;'">
                <option value="0">Selecione um especialista...</option>
            </select>
            <input type="hidden" class="expert-id-destaque" value="${destaque.especialista || destaque.expert || '0'}">
        </div>
    `;
    container.appendChild(card);

    // Toggle da seção de especialista quando checkbox mudar
    const expertCheckbox = card.querySelector('.expert-destaque');
    const especialistaSection = card.querySelector('.especialista-section');
    expertCheckbox.addEventListener('change', function () {
        especialistaSection.style.display = this.checked ? 'block' : 'none';
    });

    // Carregar especialistas no select e abrir formulário se já existir especialista
    const selectElement = card.querySelector('.especialista-select');
    const especialistaId = destaque.especialista || destaque.expert || '0';
    newsCarregarEspecialistas(selectElement, especialistaId, function () {
        // Callback após carregar especialistas
        if (especialistaId && especialistaId !== '0') {
            // Se já existe especialista, criar o formulário com os dados
            const $card = $(card);
            criarFormularioEspecialistaComDados($card, destaque);
        }
    });
}


// Função para criar formulário de especialista com dados existentes
function criarFormularioEspecialistaComDados(card, destaque) {
    const especialistaId = destaque.expert || destaque.especialista || '0';

    if (!especialistaId || especialistaId === '0') return;

    // Remove qualquer formulário anterior
    card.find('.form-especialista').remove();

    const formHtml = `
        <div class="form-especialista"
             style="margin-top:20px; padding:20px; border-radius:10px; background:#1e2336; border:1px solid #4f5b8c; color:#fff;">

            <h3 style="margin-bottom:15px; color:#90a0e8;">
                Cadastro de Comentário do Especialista
            </h3>

            <div style="margin-bottom:10px;">
                <label>Dia do conteúdo</label><br>
                <select class="dia-conteudo-especialista news-input" style="width:100%; color:#fff; fontw-eight:bold; background:#242a42;">
                    ${Array.from({ length: 20 }, (_, i) => {
        const val = i + 1;
        const selected = (destaque.ordem == val || destaque.dia_conteudo == val) ? 'selected' : '';
        return `<option value="${val}" ${selected}>${val}</option>`;
    }).join('')}
                </select>
            </div>

            <div style="margin-bottom:15px;">
                <label>Especialista selecionado</label><br>
                <input type="text" readonly value="${especialistaId}"
                       class="especialista-id-view news-input" style="width:100%; background:#2e3652;">
            </div>

            <div style="margin-bottom:12px;">
                <label>Título</label><br>
                <input type="text" class="titulo-news-especialista news-input" style="width:100%;" value="${destaque.titulo || destaque.titulo_news || ''}">
                <label style="margin-left:10px;">
                    <input type="checkbox" class="titulo-oculto-especialista" ${destaque.titulo_ativo ? 'checked' : ''}>
                    <small>Título Oculto</small>
                </label>
            </div>

            <div style="margin-bottom:12px;">
                <label>Subtítulo</label><br>
                <input type="text" class="subtitulo-news-especialista news-input" style="width:100%;" value="${destaque.subtitulo || destaque.sub_titulo_news || ''}">
            </div>

            <div style="margin-bottom:12px;">
                <label>Descritivo News Reduzida</label><br>
                <textarea class="link-endereco-especialista news-input" rows="4" maxlength="170" style="width:100%;">${destaque.link_endereco || ''}</textarea>
            </div>

            <div style="margin-bottom:12px;">
                <label>Foto de link</label><br>
                <input type="text" class="img-link-especialista news-input" style="width:100%;" value="${destaque.img_link || ''}">
                <label style="margin-left:10px;">
                    <input type="checkbox" class="link-ativo-especialista" ${destaque.link_ativo ? 'checked' : ''}>
                    <small>Link Ativo</small>
                </label>
            </div>

            <div style="margin-bottom:12px;">
                <label>Descritivo</label><br>
                <textarea class="descritivo-especialista news-input" rows="6" style="width:100%;">${destaque.descricao || destaque.descritivo_conteudo || ''}</textarea>
            </div>

            <div style="margin-bottom:12px;">
                <label>Foto (Imagem Principal)</label><br>
                <input type="text" class="img1-especialista news-input" style="width:100%;" value="${destaque.imagem || destaque.img1_conteudo || ''}">
            </div>

            <div style="margin-bottom:12px;">
                <label>Foto para reduzida</label><br>
                <input type="text" class="img-reduz-especialista news-input" style="width:100%;" value="${destaque.imagem_reduzida || destaque.img_reduz || ''}">
            </div>

            <div style="margin-bottom:12px;">
                <label>Texto alternativo (alt)</label><br>
                <input type="text" class="alt-especialista news-input" style="width:100%;" value="${destaque.alt || ''}">
            </div>

            <div style="margin-bottom:12px;">
                <label>Layout</label><br>

                <div style="line-height:28px; margin-top:5px;">
                    <img src="images/foto_esq.jpg">
                    <input type="radio" name="lay_especialista_${especialistaId}" value="1" ${(destaque.layout === '1' || destaque.layout_news === '1' || !destaque.layout) ? 'checked' : ''}>
                    Imagem à esquerda<br>

                    <img src="images/sem_foto.jpg">
                    <input type="radio" name="lay_especialista_${especialistaId}" value="2" ${(destaque.layout === '2' || destaque.layout_news === '2') ? 'checked' : ''}>
                    Não incluir imagem<br>

                    <img src="images/foto_dir.jpg">
                    <input type="radio" name="lay_especialista_${especialistaId}" value="3" ${(destaque.layout === '3' || destaque.layout_news === '3') ? 'checked' : ''}>
                    Imagem à direita<br>

                    <img src="images/so_foto.jpg">
                    <input type="radio" name="lay_especialista_${especialistaId}" value="4" ${(destaque.layout === '4' || destaque.layout_news === '4') ? 'checked' : ''}>
                    Somente imagem<br>

                    <img src="images/foto_topo.jpg">
                    <input type="radio" name="lay_especialista_${especialistaId}" value="5" ${(destaque.layout === '5' || destaque.layout_news === '5') ? 'checked' : ''}>
                    Foto Topo<br>
                </div>
            </div>

            <input type="hidden" class="expert-id" value="${especialistaId}">

            <div style="margin-top: 20px; text-align: center;">
                <button type="button" class="news-btn-modern news-btn-primary-modern" onclick="salvarComentarioEspecialista(this)" style="padding: 10px 20px;">
                    <i class="fas fa-save"></i> Salvar Comentário do Especialista
                </button>
            </div>
        </div>
    `;

    // adiciona o form dentro do card
    card.append(formHtml);
}

function criarFormularioEspecialista(card, especialistaId) {

    // Remove qualquer formulário anterior
    card.find('.form-especialista').remove();

    const formHtml = `
        <div class="form-especialista" 
             style="margin-top:20px; padding:20px; border-radius:10px; background:#1e2336; border:1px solid #4f5b8c; color:#fff;">

            <h3 style="margin-bottom:15px; color:#90a0e8;">
                Cadastro de Comentário do Especialista
            </h3>

            <div style="margin-bottom:10px;">
                <label>Dia do conteúdo</label><br>
                <select class="dia-conteudo-especialista news-input" style="width:100%;">
                    ${Array.from({ length: 20 }, (_, i) => `<option value="${i + 1}">${i + 1}</option>`).join('')}
                </select>
            </div>

            <div style="margin-bottom:15px;">
                <label>Especialista selecionado</label><br>
                <input type="text" readonly value="${especialistaId}" 
                       class="especialista-id-view news-input" style="width:100%; background:#2e3652;">
            </div>

            <div style="margin-bottom:12px;">
                <label>Título</label><br>
                <input type="text" class="titulo-news-especialista news-input" style="width:100%;">
                <label style="margin-left:10px;">
                    <input type="checkbox" class="titulo-oculto-especialista"> 
                    <small>Título Oculto</small>
                </label>
            </div>

            <div style="margin-bottom:12px;">
                <label>Subtítulo</label><br>
                <input type="text" class="subtitulo-news-especialista news-input" style="width:100%;">
            </div>

            <div style="margin-bottom:12px;">
                <label>Descritivo News Reduzida</label><br>
                <textarea class="link-endereco-especialista news-input" rows="4" maxlength="170" style="width:100%;"></textarea>
            </div>

            <div style="margin-bottom:12px;">
                <label>Foto de link</label><br>
                <input type="text" class="img-link-especialista news-input" style="width:100%;">
                <label style="margin-left:10px;">
                    <input type="checkbox" class="link-ativo-especialista"> 
                    <small>Link Ativo</small>
                </label>
            </div>

            <div style="margin-bottom:12px;">
                <label>Descritivo</label><br>
                <textarea class="descritivo-especialista news-input" rows="6" style="width:100%;"></textarea>
            </div>

            <div style="margin-bottom:12px;">
                <label>Foto (Imagem Principal)</label><br>
                <input type="text" class="img1-especialista news-input" style="width:100%;">
            </div>

            <div style="margin-bottom:12px;">
                <label>Foto para reduzida</label><br>
                <input type="text" class="img-reduz-especialista news-input" style="width:100%;">
            </div>

            <div style="margin-bottom:12px;">
                <label>Texto alternativo (alt)</label><br>
                <input type="text" class="alt-especialista news-input" style="width:100%;">
            </div>

            <div style="margin-bottom:12px;">
                <label>Layout</label><br>

                <div style="line-height:28px; margin-top:5px;">
                    <img src="images/foto_esq.jpg">  
                    <input type="radio" name="lay_especialista_${especialistaId}" value="1" checked>
                    Imagem à esquerda<br>

                    <img src="images/sem_foto.jpg">  
                    <input type="radio" name="lay_especialista_${especialistaId}" value="2">
                    Não incluir imagem<br>

                    <img src="images/foto_dir.jpg">  
                    <input type="radio" name="lay_especialista_${especialistaId}" value="3">
                    Imagem à direita<br>

                    <img src="images/so_foto.jpg">  
                    <input type="radio" name="lay_especialista_${especialistaId}" value="4">
                    Somente imagem<br>

                    <img src="images/foto_topo.jpg"> 
                    <input type="radio" name="lay_especialista_${especialistaId}" value="5">
                    Foto Topo<br>
                </div>
            </div>

            <input type="hidden" class="expert-id" value="${especialistaId}">

            <div style="margin-top: 20px; text-align: center;">
                <button type="button" class="news-btn-modern news-btn-primary-modern" onclick="salvarComentarioEspecialista(this)" style="padding: 10px 20px;">
                    <i class="fas fa-save"></i> Salvar Comentário do Especialista
                </button>
            </div>
        </div>
    `;

    // adiciona o form dentro do card
    card.append(formHtml);
}



window.newsAdicionarDestaque = function () {
    const container = document.getElementById('newsDestaquesContainer');
    const total = container.children.length;
    if (container.innerHTML.includes('Nenhum destaque')) container.innerHTML = '';
    newsCriarCardDestaque({}, total);
};

// Remover destaque com confirmação
window.newsRemoverDestaque = function (button, destaqueId) {

    if (!confirm('Deseja realmente excluir este destaque?')) {
        return;
    }
    console.log(destaqueId)
    // Se o destaque tem ID (já existe no banco), faz requisição para API
    if (destaqueId && destaqueId !== 0) {
        $.ajax({
            url: `api/newsletters.php?request=excluir_destaque&id=${destaqueId}`,
            method: 'DELETE',
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    alert('Destaque excluído com sucesso!');
                    button.parentElement.remove();

                    // Verificar se não há mais destaques
                    const container = document.getElementById('newsDestaquesContainer');
                    if (container.children.length === 0) {
                        container.innerHTML = '<p style="color: #9ca3af; text-align:center; padding: 30px;">Nenhum destaque cadastrado</p>';
                    }
                } else {
                    alert('Erro ao excluir destaque: ' + (response.error || 'Falha na exclusão'));
                }
            },
            error: function (xhr, status, error) {
                console.error('Erro ao excluir destaque:', xhr.responseText);
                alert('Erro ao excluir destaque!');
            }
        });
    } else {
        // Se não tem ID, apenas remove do DOM (destaque novo que ainda não foi salvo)
        button.parentElement.remove();

        // Verificar se não há mais destaques
        const container = document.getElementById('newsDestaquesContainer');
        if (container.children.length === 0) {
            container.innerHTML = '<p style="color: #9ca3af; text-align:center; padding: 30px;">Nenhum destaque cadastrado</p>';
        }
    }
};

// Fechar modal edição
window.newsFecharModalEdit = function () {
    document.getElementById('newsModalEdit').style.display = 'none';
};

// Salvar edição
window.newsSalvarEdicao = function () {
    if (!confirm('Confirma as alterações na newsletter?')) return;

    const id = document.getElementById('editNewsId').value;

    // Coletar destaques
    const destaques = [];
    document.querySelectorAll('#newsDestaquesContainer .destaque-card').forEach(card => {
        const isExpert = card.querySelector('.expert-destaque').checked;
        const expertId = isExpert ? (card.querySelector('.especialista-select').value || '0') : '0';



        let destaque = {
            dia_conteudo: parseInt(card.querySelector('.ordem-destaque').value) || 1,
            titulo_news: card.querySelector('.titulo-destaque').value,
            subtitulo: card.querySelector('.subtitulo-destaque').value,
            link_endereco: card.querySelector('.link-endereco-destaque').value,
            img_link: card.querySelector('.img-link-destaque').value,
            link_ativo: card.querySelector('.link-ativo-destaque').checked,
            descritivo_conteudo: card.querySelector('.descritivo-conteudo-destaque').value,
            img1_conteudo: card.querySelector('.img-destaque').value,
            img_reduz: card.querySelector('.img-reduz-destaque').value,
            alt: card.querySelector('.alt-destaque').value,
            layout_news: card.querySelector('.layout-destaque').value,
            exibe_destaque: card.querySelector('.exibir-destaque').checked,
            expert: expertId
        };

        // SE FOR ESPECIALISTA, SOBRESCREVER CAMPOS
        if (isExpert && expertId !== '0') {
            destaque.dia_conteudo = card.querySelector('.dia-conteudo-especialista')?.value || destaque.dia_conteudo;
            destaque.titulo_news = card.querySelector('.titulo-news-especialista')?.value || destaque.titulo_news;
            destaque.subtitulo = card.querySelector('.subtitulo-news-especialista')?.value || destaque.subtitulo;
            destaque.link_endereco = card.querySelector('.link-endereco-especialista')?.value || destaque.link_endereco;
            destaque.img_link = card.querySelector('.img-link-especialista')?.value || destaque.img_link;
            destaque.link_ativo = card.querySelector('.link-ativo-especialista')?.checked || destaque.link_ativo;
            destaque.descritivo_conteudo = card.querySelector('.descritivo-especialista')?.value || destaque.descritivo_conteudo;
            destaque.img1_conteudo = card.querySelector('.img1-especialista')?.value || destaque.img1_conteudo;
            destaque.img_reduz = card.querySelector('.img-reduz-especialista')?.value || destaque.img_reduz;
            destaque.alt = card.querySelector('.alt-especialista')?.value || destaque.alt;

            const layout = card.querySelector(`input[name="lay_especialista_${expertId}"]:checked`);
            if (layout) destaque.layout_news = layout.value;
        }

        destaques.push(destaque);

    });

    // Obter empresa selecionada
    const empresaSelecionada = document.querySelector('input[name="editEmpresa"]:checked');
    const empresaValue = empresaSelecionada ? empresaSelecionada.value : null;

    const payload = {
        nome: document.getElementById('editNome').value,
        data_extenso: document.getElementById('editDataExtenso').value || null,
        titulo: document.getElementById('editTitulo').value,
        empresa: empresaValue,
        cor_pe: document.getElementById('editCorPe').value || null,

        pdf: document.getElementById('editPdf').value || null,
        bloco_livre: document.getElementById('editBlocoLivre').value || null,
        chamada1_bloco: document.getElementById('editChamada1Bloco').value || null,
        chamada_bloco: document.getElementById('editChamadaBloco').value || null,
        more_poducts: document.getElementById('editMoreProducts').value || null,

        img_topo: document.getElementById('editImgTopo').value || null,
        alt_topo: document.getElementById('editAltTopo').value || null,
        foto_bloco: document.getElementById('editFotoBloco').value || null,
        alt_livre: document.getElementById('editAltLivre').value || null,

        ativo_web: document.getElementById('editAtivoWeb').checked,
        ativo_home: document.getElementById('editAtivoHome').checked,
        ativo_passion: document.getElementById('editAtivoPassion').checked,
        ativo_be: document.getElementById('editAtivoBe').checked,
        titulo_ativo: document.getElementById('editTituloAtivo').checked,
        recep: document.getElementById('editRecep').checked,
        is_header_italiano: document.getElementById('editHeaderItaliano').checked,
        novo_layout: document.getElementById('editNovoLayout').checked,

        destaques: destaques.length > 0 ? destaques : null
    };

    $.ajax({
        url: `api/newsletters.php?request=atualizar_news_completa&id=${id}`,
        method: 'PUT',
        contentType: 'application/json',
        data: JSON.stringify(payload),
        success: function (resp) {
            if (resp.success) {
                alert('Newsletter atualizada com sucesso!');
                newsFecharModalEdit();
                newsLoadData(typeof newsCurrentPage !== 'undefined' ? newsCurrentPage : 1);
            } else {
                alert('Erro: ' + (resp.error || 'Falha ao salvar'));
            }
        },
        error: function (xhr, status, error) {
            console.error('Erro ao salvar:', xhr.responseText);
            alert('Erro na comunicação com o servidor. Verifique o console para mais detalhes.');
        }
    });
};

// Fechar modal de inserção
window.newsFecharModalInsert = function () {
    document.getElementById('newsModalInsert').style.display = 'none';
};

// Salvar inserção de nova newsletter
window.newsSalvarInsercao = function () {
    // Validar campo obrigatório
    const nome = document.getElementById('insertNome').value;
    if (!nome || nome.trim() === '') {
        alert('Por favor, preencha o nome da newsletter!');
        return;
    }

    if (!confirm('Confirma a criação desta nova newsletter?')) return;

    // Obter empresa selecionada
    const empresaSelecionada = document.querySelector('input[name="insertEmpresa"]:checked');
    const empresaValue = empresaSelecionada ? empresaSelecionada.value : '1';

    const payload = {
        nome: nome,
        data_extenso: document.getElementById('insertDataExtenso').value || null,
        titulo: document.getElementById('insertTitulo').value || null,
        empresa: empresaValue,
        cor_pe: document.getElementById('insertCorPe').value || 'F9020E',

        pdf: document.getElementById('insertPdf').value || null,
        bloco_livre: document.getElementById('insertBlocoLivre').value || null,
        chamada1_bloco: document.getElementById('insertChamada1Bloco').value || null,
        chamada_bloco: document.getElementById('insertChamadaBloco').value || null,
        more_poducts: document.getElementById('insertMoreProducts').value || null,

        img_topo: document.getElementById('insertImgTopo').value || null,
        alt_topo: document.getElementById('insertAltTopo').value || null,
        foto_bloco: document.getElementById('insertFotoBloco').value || null,
        alt_livre: document.getElementById('insertAltLivre').value || null,

        ativo_web: document.getElementById('insertAtivoWeb').checked,
        ativo_home: document.getElementById('insertAtivoHome').checked,
        ativo_passion: document.getElementById('insertAtivoPassion').checked,
        ativo_be: document.getElementById('insertAtivoBe').checked,
        titulo_ativo: document.getElementById('insertTituloAtivo').checked,
        recep: document.getElementById('insertRecep').checked,
        is_header_italiano: document.getElementById('insertHeaderItaliano').checked,
        novo_layout: document.getElementById('insertNovoLayout').checked
    };

    $.ajax({
        url: 'api/newsletters.php?request=criar_news',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(payload),
        success: function (resp) {
            if (resp.success) {
                alert('Newsletter criada com sucesso!');
                newsFecharModalInsert();
                newsLoadData(1); // Voltar para a primeira página
            } else {
                alert('Erro: ' + (resp.error || 'Falha ao criar newsletter'));
            }
        },
        error: function (xhr, status, error) {
            console.error('Erro ao criar newsletter:', xhr.responseText);
            alert('Erro na comunicação com o servidor. Verifique o console para mais detalhes.');
        }
    });
};

// Visualizar newsletter nos diferentes sites
window.newsVisualizarSites = function (id) {
    const sites = [{
        nome: 'Blumar',
        url: `https://www.blumar.com.br/client_area/newsletter_blumar.php?pk_news=${id}`
    },
    {
        nome: 'BeBrazil',
        url: `https://www.bebrazildmc.com.br/client_area/newsletter_bebrazil.php?pk_news=${id}`
    },
    {
        nome: 'Seleto',
        url: `https://www.seletobrazil.com.br/client_area/newsletter_seleto.php?pk_news=${id}`
    },
    {
        nome: 'Riolife',
        url: `https://riolifetours.com/tariff/newsletter.php?pk_news=${id}`
    }
    ];

    let linksHtml = '<div style="padding: 20px;">';
    linksHtml += '<h4 style="margin-bottom: 20px; color: #5a67d8;"><i class="fas fa-external-link-alt"></i> Visualizar Newsletter nos Sites</h4>';
    linksHtml += '<div style="display: grid; gap: 15px;">';

    sites.forEach(site => {
        linksHtml += `
                <a href="${site.url}" target="_blank"
                   style="padding: 15px; background: rgba(90, 103, 216, 0.1); border: 1px solid rgba(90, 103, 216, 0.3);
                          border-radius: 8px; color: #5a67d8; text-decoration: none; display: flex; align-items: center;
                          justify-content: space-between; transition: all 0.3s;">
                    <span><i class="fas fa-globe"></i> ${site.nome}</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            `;
    });

    linksHtml += '</div></div>';

    document.getElementById('newsViewContent').innerHTML = linksHtml;
    document.getElementById('newsModalView').style.display = 'block';
};

// Duplicar newsletter
window.newsDuplicarItem = function (id) {
    if (!confirm('Deseja duplicar esta newsletter? Uma cópia será criada.')) return;

    $.ajax({
        url: `api/newsletters.php?request=duplicar_news&id=${id}`,
        method: 'POST',
        dataType: 'json',
        success: function (resp) {
            if (resp.success) {
                alert(`Newsletter duplicada com sucesso! ID da cópia: ${resp.novo_id || 'N/A'}`);
                newsLoadData(1);
            } else {
                alert('Erro ao duplicar: ' + (resp.error || 'Falha na duplicação'));
            }
        },
        error: function (xhr, status, error) {
            console.error('Erro ao duplicar:', xhr.responseText);
            alert('Erro na comunicação com o servidor.');
        }
    });
};

// Carregar lista de especialistas
function newsCarregarEspecialistas(selectElement, expertIdSelecionado, callback) {
    $.ajax({
        url: 'api/newsletters.php?request=listar_especialistas',
        method: 'GET',
        dataType: 'json',
        success: function (especialistas) {
            selectElement.innerHTML = '<option value="0">Selecione um especialista...</option>';

            if (especialistas && especialistas.length > 0) {
                especialistas.forEach(function (expert) {
                    const selected = (expert.pk_br_experts == expertIdSelecionado) ? 'selected' : '';
                    selectElement.innerHTML += `<option value="${expert.pk_br_experts}" ${selected}>${expert.nome}</option>`;
                });
            }

            // Executar callback se fornecido
            if (typeof callback === 'function') {
                callback();
            }
        },
        error: function (xhr) {
            console.error('Erro ao carregar especialistas:', xhr.responseText);
            selectElement.innerHTML = '<option value="0">Erro ao carregar especialistas</option>';
        }
    });
}

// Quando escolher um especialista
$(document).on('change', '.especialista-select', function () {

    const card = $(this).closest('.destaque-card'); // pega o card atual
    const especialistaId = $(this).val();           // ID do especialista selecionado

    if (especialistaId == 0) {
        card.find('.form-especialista').remove();   // remove formulário se escolher "0"
        return;
    }

    // Chama a função que cria o formulário
    criarFormularioEspecialista(card, especialistaId);
});

// Função para salvar comentário do especialista
window.salvarComentarioEspecialista = function (button) {
    const formEspecialista = $(button).closest('.form-especialista');

    // Validação básica
    const especialistaId = formEspecialista.find('.expert-id').val();
    if (!especialistaId || especialistaId === '0') {
        alert('Por favor, selecione um especialista válido!');
        return;
    }

    const tituloNews = formEspecialista.find('.titulo-news-especialista').val();
    if (!tituloNews || tituloNews.trim() === '') {
        alert('Por favor, preencha o título do comentário do especialista!');
        return;
    }

    if (!confirm('Confirma o salvamento deste comentário de especialista?')) {
        return;
    }

    // Coletar dados do formulário de especialista
    const tituloOculto = formEspecialista.find('.titulo-oculto-especialista').is(':checked');
    const linkAtivo = formEspecialista.find('.link-ativo-especialista').is(':checked');
    const layoutSelecionado = formEspecialista.find(`input[name^="lay_especialista_"]:checked`).val() || '1';

    const newsId = $('#editNewsId').val();

    const payload = {
        pk_news: newsId,
        dia_conteudo: formEspecialista.find('.dia-conteudo-especialista').val(),
        titulo_news: tituloNews,
        subtitulo: formEspecialista.find('.subtitulo-news-especialista').val(),
        link_endereco: formEspecialista.find('.link-endereco-especialista').val(),
        img_link: formEspecialista.find('.img-link-especialista').val(),
        descritivo_conteudo: formEspecialista.find('.descritivo-especialista').val(),
        img1_conteudo: formEspecialista.find('.img1-especialista').val(),
        img_reduz: formEspecialista.find('.img-reduz-especialista').val(),
        alt: formEspecialista.find('.alt-especialista').val(),
        expert: especialistaId,
        layout_news: layoutSelecionado,
        titulo_ativo: tituloOculto,
        link_ativo: linkAtivo,
        exibe_destaque: true
    };

    console.log('Salvando comentário de especialista:', payload);

    $.ajax({
        url: 'api/newsletters.php?request=inserir_comentario_especialista',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(payload),
        success: function (resp) {
            if (resp.success) {
                alert('Comentário do especialista salvo com sucesso!');

                console.log('Comentário de especialista #' + resp.id + ' criado com sucesso!');

                // Mantém tudo visível (checkbox marcado, select selecionado, formulário aberto)
                // Não remove nada, apenas mostra mensagem de sucesso
            } else {
                alert('Erro ao salvar comentário: ' + (resp.error || 'Falha na operação'));
            }
        },
        error: function (xhr) {
            console.error('Erro ao salvar comentário de especialista:', xhr.responseText);
            alert('Erro na comunicação com o servidor. Verifique o console para mais detalhes.');
        }
    });
};



