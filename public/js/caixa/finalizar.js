"use strict";
import('./atendimento.js').then(module => module.iniciarAtendimento('venda')).catch(() => MPBAlert.error('Não foi possível carregar o atendimento. Atualize a página.'));
