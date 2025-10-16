import pyautogui
import pyperclip
import time
sxrx=0

def click(x, y):
    pyautogui.moveTo(x, y)
    pyautogui.click()

def simulate_typing(text):
    pyautogui.write(text, interval=0.05)
    pyautogui.press('enter')

def scroll_at(x, y, duration=10, amount=-10):
    pyautogui.moveTo(x, y)
    start_time = time.time()
    while time.time() - start_time < duration:
        pyautogui.scroll(amount)
        time.sleep(0.2)

def get_clipboard():
    return pyperclip.paste()
time.sleep(3)
# Step 1: Click input box
click(sxrx+899,916)
x=input()
# Step 2: Type message and press Enter
simulate_typing(x)
click(sxrx+1696,916)
# Step 3: Wait for output to load
time.sleep(1)
click(sxrx+103,74)
# Step 4: Scroll to load more (if needed)
time.sleep(3)
rgb=(64, 64, 64)
while rgb==pyautogui.pixel(sxrx+1696,916):
    time.sleep(1)
scroll_at(sxrx+1225,555, duration=2, amount=-2000)

# Step 5: Click to select or copy output
click(sxrx+650, 605)

# Wait for clipboard to update (if needed)
time.sleep(1)

# Step 6: Read clipboard
print("Clipboard contains:", get_clipboard())
