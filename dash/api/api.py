from flask import Flask, request, jsonify
from flask_cors import CORS  # <--- import
import pyautogui
import pyperclip
import time
import re
sxrx=0

def click(x, y):
    pyautogui.moveTo(x, y)
    pyautogui.click()

def simulate_typing(text):
    pyautogui.write(text, interval=0.05)

def scroll_at(x, y, duration=10, amount=-10):
    pyautogui.moveTo(x, y)
    start_time = time.time()
    while time.time() - start_time < duration:
        pyautogui.scroll(amount)
        time.sleep(0.2)

def get_clipboard():
    return pyperclip.paste()
app = Flask(__name__)
CORS(app)  # <--- enable CORS for all routes

@app.route('/greet', methods=['POST'])
def greet():
    data = request.get_json()
    
    if not data or 'name' not in data:
        return jsonify({"error": "Please provide a 'name' in JSON"}), 400
    
    name = data['name']

    time.sleep(3)
    # Step 1: Click input box
    click(sxrx+899,916)
    
    # Step 2: Type message and press Enter
    simulate_typing(name)
    click(sxrx+1751,911)
    # Step 3: Wait for output to load
    time.sleep(4)
    click(sxrx+116,77)
    # Step 4: Scroll to load more (if needed)
    time.sleep(7)
    rgb=(64, 64, 64)
    while rgb==pyautogui.pixel(sxrx+1749,908):
        time.sleep(1)
    scroll_at(sxrx+1225,555, duration=5, amount=-10000)

    # Step 5: Click to select or copy output
    click(sxrx+625, 597)

    # Wait for clipboard to update (if needed)
    time.sleep(1)

    # Step 6: Read clipboard
    print("Clipboard contains:", get_clipboard())
    x = get_clipboard()

    # ✅ Safely apply regex before jsonify
    formatted = re.sub(r"\*\*(.*?)\*\*", r"<b>\1</b>", x)

    return jsonify({"reply": formatted}), 200

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
