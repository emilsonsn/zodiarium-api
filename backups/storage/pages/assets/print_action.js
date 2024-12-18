// Criação do botão flutuante
const button = document.createElement('button');
button.innerText = 'Baixar relatório';
button.style.position = 'fixed';
button.style.bottom = '20px';
button.style.right = '20px';
button.style.zIndex = '1000';
button.style.padding = '10px 20px';
button.style.backgroundColor = 'green';
button.style.color = '#fff';
button.style.border = 'none';
button.style.borderRadius = '5px';
button.style.width = '400px';
button.style.height = '100px';
button.style.fontSize = '2rem';
button.style.cursor = 'pointer';
button.style.boxShadow = '0px 4px 6px rgba(0,0,0,0.1)';
setTimeout(() => {
    document.body.appendChild(button);
}, 1000);


// Evento de clique para capturar e abrir a tela de impressão
button.addEventListener('click', function () {
    // Cria uma nova janela para impressão com todo o conteúdo da página
    myTabContent = document.querySelector('#myTabContent');
    myTabContent.style.height = 'auto';
    this.style.display = 'none';
    setTimeout(() => {
        myTabContent.focus();
        window.print();
        this.style.display = 'fixed';
        myTabContent.style.height = '100vh';    
    })
});
