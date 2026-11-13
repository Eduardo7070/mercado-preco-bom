"use strict";
import('./atendimento.js').then(module => module.iniciarAtendimento('cancelar')).catch(() => MPBAlert.error('Não foi possível carregar o atendimento. Atualize a página.'));
