from playwright.sync_api import sync_playwright

def verify_app():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context()
        page = context.new_page()

        # 1. Login
        print("Navigating to login page...")
        page.goto("http://localhost:8000/index.php")
        page.fill("#username", "3213907836")
        page.fill("#password", "74181532")
        page.click("button[type=submit]")

        # Wait for dashboard
        print("Waiting for dashboard...")
        page.wait_for_url("**/dashboard.php")

        # 2. Check Route Section
        print("Checking Route Section...")
        page.click("text=Organización de Rutas")
        page.click("text=Lunes")
        page.wait_for_selector("#route-content:not(.hidden)")

        # Screenshot Route Section
        page.screenshot(path="verification/route_section.png")
        print("Captured route_section.png")

        # 3. Check Add Stop Form
        print("Checking Add Stop Form...")
        page.click("text=Agregar Nuevo Comercio")
        page.wait_for_selector("#add-stop-form:not(.hidden)")

        # Screenshot Form
        page.screenshot(path="verification/add_stop_form.png")
        print("Captured add_stop_form.png")

        # 4. Check Accounts Section and Calculation
        print("Checking Accounts Section...")
        page.click("text=Generar Cuentas del Día")
        page.wait_for_selector("#cuentas_dia:not(.hidden)")

        page.fill("input[name=planilla_number]", "123")
        page.fill("input[name=total_planilla]", "1000")
        page.fill("input[name=total_devoluciones]", "100")
        page.fill("input[name=parciales]", "50")

        # Expect calculated Total Consignar = 1000 - 150 = 850
        page.wait_for_function("document.getElementById('total_consignar').value == '850.00'")

        page.fill("input[name=total_consignado]", "800")
        page.fill("input[name=total_qr]", "0")

        # Expect calculated Total Entrega Quala = 800
        page.wait_for_function("document.getElementById('total_entrega_quala').value == '800.00'")

        # Expect Descuadre = 850 - 800 = 50 (Positive -> Green)
        page.wait_for_function("document.getElementById('total_descuadre').value == '50.00'")

        # Screenshot Accounts
        page.screenshot(path="verification/accounts_calculation.png")
        print("Captured accounts_calculation.png")

        browser.close()

if __name__ == "__main__":
    verify_app()
