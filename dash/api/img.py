from flask import Flask, request, jsonify
from flask_cors import CORS  # <--- import
import pyautogui
import time
import re
import random
sxrx=0

def click(x, y):
    pyautogui.moveTo(x, y)
    pyautogui.click()

def simulate_typing(text):
    pyautogui.write(text, interval=0.01)

def scroll_at(x, y, duration=10, amount=-10):
    pyautogui.moveTo(x, y)
    start_time = time.time()
    while time.time() - start_time < duration:
        pyautogui.scroll(amount)
        time.sleep(0.2)
app = Flask(__name__)
CORS(app)  # <--- enable CORS for all routes

@app.route('/greet', methods=['POST'])
def greet():
    rand=random.randint(1000,9999)
    data = request.get_json()
    
    if not data or 'name' not in data:
        return jsonify({"error": "Please provide a 'name' in JSON"}), 400
    
    name = data['name']

    time.sleep(10)
    # Step 1: Click input box
    click(sxrx+793,791)
    
    # Step 2: Type message and press Enter
    simulate_typing(name+" create an image on that")
    click(sxrx+1814,885)
    # Step 3: Wait for output to load
    time.sleep(4)
    rgb=(168,199,250)
    while rgb==pyautogui.pixel(sxrx+1815,882):
        time.sleep(1)
    click(sxrx+104,73)
    # Step 4: Scroll to load more (if needed)
    time.sleep(7)

    scroll_at(sxrx+1225,555, duration=5, amount=-10000)

    # Step 5: Click to select or copy output
    click(1024,443)
    pyautogui.rightClick(1024,443)
    time.sleep(5)
    click(sxrx+1106,510)
    time.sleep(5)
    click(sxrx+203,390)
    y=str(rand)
    simulate_typing(str(y))
    time.sleep(1)
    click(sxrx+749,525)
    # Wait for clipboard to update (if needed)
    time.sleep(1)
    y="http://localhost/drona/dash/img/unna"+y+"med.png"
    # Step 6: Read clipboard

    # ✅ Safely apply regex before jsonify

    return jsonify({"reply": y}), 200

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
