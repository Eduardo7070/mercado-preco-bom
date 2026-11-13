"use strict";
import('./movimentacao.js').then(module => module.iniciarMovimentacao('saida')).catch(() => MPBAlert.error('Não foi possível carregar a busca de produtos. Atualize a página.'));
