<?php

namespace App\Traits;

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;

trait PrintsReceipt
{
    public function printReceipt($patient, $services, $token = null, $long = 0)
    {
        $printerName = 'COM3';
        $defaultSize = 1;
        try {
            $connector = new WindowsPrintConnector($printerName);
            $printer = new Printer($connector);

            // --- HEADER ---
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setTextSize($defaultSize + 2, $defaultSize + 2);
            $printer->setEmphasis(true);
            $printer->text("MMC\n");

            $printer->setTextSize($defaultSize, $defaultSize);
            $printer->setEmphasis(false);
            $printer->text("Date: " . Carbon::now()->format('d M H:i') . "   ");
            $printer->setTextSize($defaultSize + 1, $defaultSize + 1);
            $printer->setEmphasis(true);
            if ($token) {
                $printer->text("Token # $token\n\n");
            }

            // --- PATIENT INFO ---
            $printer->setJustification(Printer::JUSTIFY_LEFT);

            // Line 1: Patient label (small)
            $printer->setTextSize($defaultSize, $defaultSize);
            $printer->setEmphasis(false);
            $printer->text("  Patient:" . "\n\n");

            // Line 2: Actual Patient Name (bold and large)
            $printer->setTextSize($defaultSize + 1, $defaultSize);
            $printer->setEmphasis(true);
            $printer->text("  " . strtoupper($patient->name) . "\n\n");

            // Line 3: Contact and Age (small)
            $printer->setTextSize($defaultSize, $defaultSize);
            $printer->setEmphasis(false);
            $contact = $patient->contact ?? '';
            $age = $patient->age ?? '';
            $line = sprintf("  Contact: %-27s Age: %s\n", $contact, $age);
            $printer->text($line);



            // --- BODY / SERVICES ---
            $printer->setEmphasis(true);
            $printer->setTextSize(1, 2);

            // --- START SERVICES ---
            $printer->text("\n");
            $printer->text("------------------- Services -------------------\n");

            $total = 0;

            foreach ($services as $item) {
                $name = mb_strimwidth($item['name'], 0, 26, '');
                $price = number_format($item['charged_price']);
                $total += $item['charged_price'];

                // Adjust left margin and column width
                $printer->text(sprintf("    %-26s %8s\n", $name, 'Rs. ' . $price));
            }

            // --- END SERVICES ---
            $printer->text("------------------------------------------------\n");

            // --- TOTAL ---
            $printer->setTextSize(1, 2); // Bigger total
            $printer->text(sprintf("    %-26s %8s\n", "Total", 'Rs. ' . number_format($total)));
            $printer->setTextSize(1, 1); // Reset to default
            $printer->setEmphasis(false);



            // --- FOOTER SPACING ---
            if ($long == 1) {
                $printer->feed(14); // Extra space if referred by MO
            } else {
                $printer->feed(1);
            }

            // --- FOOTER ---
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setTextSize($defaultSize, $defaultSize);
            $printer->setEmphasis(false);
            $printer->text("Thanks For Visiting MMC.\n");
            $printer->text("Contact us At 03208489685 , 04236662345\n");

            // --- FINALIZE ---
            $printer->feed(2);
            $printer->cut();
            $printer->close();
        } catch (\Exception $e) {
            // Optional: log error
            Log::error("Receipt print failed: " . $e->getMessage());
        }
    }

    public function printLabReceipt($patient, $tests, $totalOriginal, $discountPercent = 0, $finalTotal)
    {
        $defaultSize = 1;
        $printerName = 'COM3';

        try {
            $connector = new WindowsPrintConnector($printerName);
            $printer = new Printer($connector);

            // ----- PATIENT COPY -----
            $this->printLabSlip($printer, $defaultSize, $patient, $tests, $totalOriginal, $discountPercent, $finalTotal, "Patient Copy");

            // Small gap between copies
            $printer->feed(3);

            // ----- STAFF COPY -----
            $this->printLabSlip($printer, $defaultSize, $patient, $tests, $totalOriginal, $discountPercent, $finalTotal, "Staff Copy");

            // Finalize
            $printer->cut();
            $printer->close();
        } catch (\Exception $e) {
            Log::error("Lab Receipt print failed: " . $e->getMessage());
        }
    }

    private function printLabSlip($printer, $defaultSize, $patient, $tests, $totalOriginal, $discountPercent, $finalTotal, $copyLabel)
    {
        // Header
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setTextSize($defaultSize + 2, $defaultSize + 2);
        $printer->setEmphasis(true);
        $printer->text("MMC LABS\n");

        $printer->setTextSize($defaultSize, $defaultSize);
        $printer->setEmphasis(false);
        $printer->text("Date: " . Carbon::now()->format('d M H:i') . "\n");
        $printer->setTextSize($defaultSize + 1, $defaultSize + 1);
        $printer->setEmphasis(true);
        $printer->text("{$copyLabel}\n");

        // Patient Info
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->setTextSize($defaultSize, $defaultSize);
        $printer->setEmphasis(false);
        $printer->text("  Patient:\n");
        $printer->setTextSize($defaultSize + 1, $defaultSize);
        $printer->setEmphasis(true);
        $printer->text("  " . strtoupper($patient->name) . "\n\n");

        $printer->setTextSize($defaultSize, $defaultSize);
        $contact = $patient->contact ?? '';
        $age = $patient->age ?? '';
        $line = sprintf("  Contact: %-27s Age: %s\n", $contact, $age);
        $printer->text($line);

        // Tests
        $printer->setEmphasis(true);
        $printer->setTextSize(1, 2);
        $printer->text("\n---------------------- Tests -------------------\n");
        foreach ($tests as $item) {
            $name = mb_strimwidth($item['name'], 0, 26, '');
            $price = number_format($item['original_price']);
            $printer->text(sprintf("    %-26s %8s\n", $name, 'Rs. ' . $price));
        }
        $printer->text("------------------------------------------------\n");

        // Totals
        $printer->setTextSize(1, 2);
        $printer->text(sprintf("    %-26s %8s\n", "Total", 'Rs. ' . number_format($totalOriginal)));
        $printer->text(sprintf("    %-26s %8s\n", "Discount", 'Rs. ' . number_format($discountPercent) . '%'));
        $printer->text(sprintf("    %-26s %8s\n", "Discounted Price", 'Rs. ' . number_format($finalTotal)));
        $printer->setTextSize(1, 1);
        $printer->setEmphasis(false);

        // Footer
        $printer->feed(1);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setTextSize($defaultSize, $defaultSize);
        $printer->text("Thanks For Visiting MMC.\n");
        $printer->text("Contact us At 03208489685 , 04236662345\n");

        $printer->feed(2);
    }

    public function printCasePaymentReceipt($case, $payments)
    {
        $printerName = 'COM3';
        $defaultSize = 1;
        try {
            $connector = new WindowsPrintConnector($printerName);
            $printer = new Printer($connector);

            // --- HEADER ---
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setTextSize($defaultSize + 2, $defaultSize + 2);
            $printer->setEmphasis(true);
            $printer->text("MMC\n");

            $printer->setTextSize($defaultSize, $defaultSize);
            $printer->setEmphasis(false);
            $printer->text("Date: " . Carbon::now()->format('d M Y H:i') . "\n");

            $printer->setTextSize($defaultSize + 1, $defaultSize + 1);
            $printer->setEmphasis(true);
            $printer->text("CASE PAYMENT RECEIPT\n\n");

            // --- PATIENT INFO ---
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->setTextSize($defaultSize, $defaultSize);
            $printer->setEmphasis(false);
            $printer->text("  Patient:\n");

            $printer->setTextSize($defaultSize + 1, $defaultSize);
            $printer->setEmphasis(true);
            $printer->text("  " . strtoupper($case->patient->name) . "\n\n");

            $printer->setTextSize($defaultSize, $defaultSize);
            $contact = $case->patient->contact ?? '';
            $age = $case->patient->age ?? '';
            $gender = ucfirst($case->patient->gender ?? '');
            $printer->text("  Contact: {$contact}   Age: {$age} \n  Gender: {$gender}\n\n");

            // --- CASE INFO ---
            $printer->setTextSize($defaultSize, $defaultSize);
            $printer->setEmphasis(true);
            $printer->text("  Doctor: " . $case->doctor->name . "\n");
            $printer->text("  Operation: " . $case->title . "\n");
            $printer->text("  Date: " . Carbon::parse($case->scheduled_date)->format('d M Y') . "\n");
            $printer->text("  Room: " . $case->room_type . "\n\n");

            // --- FINANCIAL SUMMARY ---
            $printer->setTextSize(1, 2);
            $printer->text("  -------- Financial Summary --------\n");

            $final = number_format($case->final_price);
            $paid = number_format($case->final_price - $case->balance);
            $balance = number_format($case->balance);

            $printer->setTextSize(1, 1);
            $printer->setEmphasis(false);
            $printer->text(sprintf("    %-25s %10s\n", "Final Package", "Rs. " . $final));
            $printer->text(sprintf("    %-25s %10s\n", "Total Paid", "Rs. " . $paid));
            $printer->text(sprintf("    %-25s %10s\n", "Balance", "Rs. " . $balance));

            // --- PAYMENT HISTORY ---
            if (count($payments)) {
                $printer->text("\n  -------- Payment History --------\n");
                foreach ($payments as $payment) {
                    $payment = $payment->payment;
                    $amount = number_format($payment->amount);
                    $method = ucfirst($payment->method);
                    $printer->text("    Rs. {$amount} via {$method}\n");
                }
            }

            // --- FOOTER ---
            $printer->feed(2);
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setTextSize($defaultSize, $defaultSize);
            $printer->setEmphasis(false);
            $printer->text("Thanks For Visiting MMC.\n");
            $printer->text("Contact: 03208489685 , 04236662345\n");

            $printer->feed(2);
            $printer->cut();
            $printer->close();
        } catch (\Exception $e) {
            Log::error("Case Receipt print failed: " . $e->getMessage());
        }
    }

    public function printShiftSummary(array $data)
    {
        try {
            $connector = new WindowsPrintConnector("COM3"); // Adjust as needed
            $printer = new Printer($connector);

            // === HEADER ===
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setTextSize(2, 2); // Big bold title
            $printer->setEmphasis(true);
            $printer->text("SHIFT SUMMARY\n");
            $printer->setTextSize(1, 1);
            $printer->setEmphasis(false);
            $printer->feed(1);

            $printer->text("Date: {$data['date']}\n");
            $printer->text("Shift: {$data['shift_label']}\n");
            $printer->feed(1);

            // === SERVICE TRANSACTIONS ===
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text(str_repeat("-", 48) . "\n");
            $printer->setEmphasis(true);
            $printer->text("SERVICE TRANSACTIONS\n");
            $printer->setEmphasis(false);
            $printer->text(str_pad("Cash:", 25) . str_pad("Rs. " . number_format($data['services_cash'], 2), 23, ' ', STR_PAD_LEFT) . "\n");
            $printer->text(str_pad("Online:", 25) . str_pad("Rs. " . number_format($data['services_online'], 2), 23, ' ', STR_PAD_LEFT) . "\n");
            $printer->text(str_pad("Total Services:", 25) . str_pad("Rs. " . number_format($data['services'], 2), 23, ' ', STR_PAD_LEFT) . "\n\n");

            // === LAB TRANSACTIONS ===
            $printer->setEmphasis(true);
            $printer->text("LAB TRANSACTIONS\n");
            $printer->setEmphasis(false);
            $printer->text(str_pad("Cash:", 25) . str_pad("Rs. " . number_format($data['labs_cash'], 2), 23, ' ', STR_PAD_LEFT) . "\n");
            $printer->text(str_pad("Online:", 25) . str_pad("Rs. " . number_format($data['labs_online'], 2), 23, ' ', STR_PAD_LEFT) . "\n");
            $printer->text(str_pad("Total Labs:", 25) . str_pad("Rs. " . number_format($data['labs'], 2), 23, ' ', STR_PAD_LEFT) . "\n\n");

            // === DEDUCTIONS ===
            $printer->setEmphasis(true);
            $printer->text("DEDUCTIONS\n");
            $printer->setEmphasis(false);
            $printer->text(str_pad("Doctor Payouts:", 25) . str_pad("-Rs. " . number_format($data['doctor_payouts'], 2), 23, ' ', STR_PAD_LEFT) . "\n");
            $printer->text(str_pad("Expenses:", 25) . str_pad("-Rs. " . number_format($data['expenses'], 2), 23, ' ', STR_PAD_LEFT) . "\n");
            $printer->text(str_pad("Return Slips:", 25) . str_pad("-Rs. " . number_format($data['returns'], 2), 23, ' ', STR_PAD_LEFT) . "\n\n");

            // === FINAL SUMMARY ===
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text("FINAL SUMMARY\n");
            $printer->setEmphasis(false);
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text(str_pad("Cash to Submit:", 25) . str_pad("Rs. " . number_format($data['final_cash'], 2), 23, ' ', STR_PAD_LEFT) . "\n");
            $printer->text(str_pad("Cash Received:", 25) . str_pad("Rs. " . number_format($data['cash_received'], 2), 23, ' ', STR_PAD_LEFT) . "\n");
            $printer->text(str_pad("Amount Less:", 25) . str_pad("Rs. " . number_format($data['amount_less'], 2), 23, ' ', STR_PAD_LEFT) . "\n");

            // === FOOTER ===
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text(str_repeat("-", 48) . "\n");
            $printer->text("Printed at: " . now()->format('d/m/Y h:i A') . "\n");
            $printer->text("Handled by: " . ($data['handler'] ?? 'User') . "\n");

            $printer->feed(3);
            $printer->cut();
            $printer->close();
        } catch (\Exception $e) {
            report($e);
        }
    }




    public function printDoctorPayoutReceipt(array $data)
    {
        try {
            $connector = new WindowsPrintConnector("COM3"); // adjust COM port
            $printer = new Printer($connector);

            // === Header ===
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setTextSize(2, 2); // Double width & height
            $printer->setEmphasis(true);
            $printer->text("DOCTOR PAYOUT\n");
            $printer->setTextSize(1, 1);
            $printer->setEmphasis(false);
            $printer->text("Dr. {$data['doctor_name']}\n");
            $printer->text("Payout Type: {$data['payout_type']}\n");
            $printer->feed(1);
            $printer->text("From: {$data['from_date']}\n");
            $printer->text("To:   {$data['to_date']}\n");
            $printer->feed(1);

            // === Summary ===
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text(str_repeat("-", 44) . "\n");
            $printer->text(str_pad("Services:", 20) . str_pad($data['total_services'], 24, ' ', STR_PAD_LEFT) . "\n");
            $printer->text(str_pad("Lab Tests:", 20) . str_pad($data['total_labs'], 24, ' ', STR_PAD_LEFT) . "\n");
            $printer->text(str_pad("Total Transactions:", 20) . str_pad($data['total_transactions'], 24, ' ', STR_PAD_LEFT) . "\n");
            $printer->setEmphasis(true);
            $printer->text(str_pad("Doctor Share:", 20) . str_pad("Rs. " . number_format($data['total_share'], 2), 28, ' ', STR_PAD_LEFT) . "\n");
            $printer->setEmphasis(false);
            $printer->text(str_repeat("-", 48) . "\n");

            // === Transactions Table ===
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->setEmphasis(true);
            $printer->text(str_pad("Type", 10) . str_pad("Patient", 20) . str_pad("Share", 18, ' ', STR_PAD_LEFT) . "\n");
            $printer->setEmphasis(false);
            $printer->text(str_repeat("-", 48) . "\n");

            foreach ($data['transactions'] as $txn) {
                $type = str_pad(substr($txn['type'], 0, 10), 10);
                $patient = str_pad(substr($txn['patient'], 0, 20), 20);
                $share = str_pad("Rs." . number_format($txn['doctor_share'], 0), 18, ' ', STR_PAD_LEFT);
                $printer->text("$type$patient$share\n");
            }

            $printer->text(str_repeat("-", 48) . "\n");

            // === Footer ===
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("🖨️ Printed: " . now()->format('d/m/Y h:i A') . "\n");
            $printer->text("Handled by: " . auth()->user()->name . "\n");

            $printer->feed(3);
            $printer->cut();
            $printer->close();
        } catch (\Exception $e) {
            report($e);
        }
    }
}
