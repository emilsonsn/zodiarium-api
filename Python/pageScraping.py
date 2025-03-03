import sys
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from bs4 import BeautifulSoup
import os
import time
from time import sleep

# Configuração do Selenium
options = Options()
options.add_argument('--headless')
options.add_argument("--no-sandbox")
options.add_argument("--disable-dev-shm-usage")
options.add_argument("--disable-gpu")
options.add_argument("--disable-software-rasterizer")
options.add_argument("--remote-debugging-port=9222")
service = Service()
driver = webdriver.Chrome(service=service, options=options)

custom_head = """
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/bootstrap.min.css">
    <link rel="stylesheet" href="assets/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/custom-style.css">
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/pdfpagewestern_style.css">
    <script src="assets/print_action.js"></script>
</head>
"""

try:
    url = sys.argv[1]
    driver.get(url)
     
    sleep(20)

    soup = BeautifulSoup(driver.page_source, 'html.parser')
    my_tab_content = soup.select_one('#myTabContent')

    final_html = f"<html>{custom_head}<body>{my_tab_content.prettify()}</body></html>"

    filename = f"page_{int(time.time())}.html"
    output_dir = os.path.join("storage", "app", "public", "pages")
    os.makedirs(output_dir, exist_ok=True)
    file_path = os.path.join(output_dir, filename)

    with open(file_path, 'w', encoding='utf-8') as file:
        file.write(final_html)

    print(file_path)
finally:
    driver.quit()
