---
title: Tunzuri User Manual
subtitle: Hospital management system — a guide for facility staff and administrators
date: October 2026
---

# About this manual

Tunzuri runs a facility's whole patient journey in one place: registration, queues, triage, consultation, laboratory, imaging, pharmacy, wards, maternity, theatre, cashier, insurance, stock and Ministry of Health reports.

This manual is for the people who use Tunzuri every day: receptionists, nurses, clinicians, laboratory and imaging staff, pharmacists, storekeepers, cashiers and the facility administrator. Each chapter covers one area of work. Read the chapters for the screens you use, plus **Getting started**.

**Conventions used in this manual**

- **Bold text** is a button, field or menu item as it appears on screen.
- Amounts are in Uganda shillings (UGX).
- "Your administrator" means the person who runs Tunzuri for your facility.

# Getting started

## Signing in

1. Open your facility's Tunzuri address in a web browser (Chrome, Edge, Firefox or Safari).
2. Enter your email address and password, then select **Log in**.
3. If you were given a temporary password, change it straight away (see *Your profile and password* below).

If you forget your password, select **Forgot your password?** on the sign-in page and follow the link sent to your email.

For safety, Tunzuri signs you out automatically after 15 minutes without activity. Anything you have not saved is lost, so save forms before stepping away.

## Finding your way around

The menu on the left shows only the areas you have access to. They are grouped as:

| Group | Screens |
| --- | --- |
| Main | Dashboard, Patients, Appointments |
| Clinical | Queues, Laboratory, Radiology, Wards, Maternity, Theatre |
| Operations | Pharmacy, Cashier, Insurance, Inventory, Stock levels, Stock usage, Reports |
| Administration | Users, Departments, Wards & beds, Price list, Audit trail |

If you need a screen you cannot see, ask your administrator to give you that area.

**Searchable lists.** Long lists, such as items, medicines, suppliers, employees, beds and visits, are search boxes rather than plain dropdowns. Click the box and type part of the name. Matches appear as you type. Use the arrow keys and **Enter**, or click a match, to choose it. The list shows extra detail (unit, price, ward) on the right of each match.

## Your profile and password

Select your name (at the top right, or at the bottom of the menu) to open your profile. Here you can update your name and email, and change your password. Use a password of at least eight characters that you do not use anywhere else.

## Who can do what

Your administrator gives each person one or more *areas*. Each area opens a set of screens.

| Area | What it covers |
| --- | --- |
| Reception | Patients, appointments, queues and the cashier |
| Clinical | Triage, consultation, wards, maternity and theatre; viewing lab, imaging and pharmacy |
| Laboratory | Receiving lab requests and releasing results |
| Imaging | Receiving imaging orders and writing reports |
| Pharmacy | Dispensing prescriptions; viewing stock levels |
| Stores | Stock, purchasing, requisitions, returns and adjustment requests |
| Reports | Facility and Ministry of Health reports |

The **administrator** has every screen. Some decisions stay with the administrator on purpose, so that the person who handles money or stock is not the same person who approves exceptions:

- Approving **stock adjustments** (expired, damaged, lost, or a count that does not match).
- Approving or rejecting **waivers** at the cashier.
- Managing users, departments, wards and prices.

Every action in Tunzuri is recorded with the name of the person who did it and the time.

# A patient's journey

Most outpatient visits follow the same path. Tunzuri moves the patient from one queue to the next as each step is finished.

1. **Reception** registers the patient (or finds their record) and opens a visit. The patient joins the **Triage** queue.
2. **Triage** records vital signs, the chief complaint and a priority. The patient joins the **Consultation** queue.
3. **Consultation**: the clinician writes notes, records diagnoses, and orders lab tests, imaging and medicines. Each order sends the patient to that queue and adds the charge to the bill automatically.
4. **Laboratory** and **Radiology** enter and release results, which appear on the visit.
5. **Cashier** takes payment for cash patients. Insurance and corporate visits are billed to the scheme.
6. **Pharmacy** dispenses the medicines. A cash patient collects them once the bill is paid.
7. The visit is **completed** once the cash balance is settled and nothing is pending. If the patient needs a bed, the clinician **admits** them to a ward instead.

Patients are always seen in this order in every queue: emergencies first, then urgent, then by arrival time.

# Reception

## Registering a patient

1. Go to **Patients** and select **Register a patient**.
2. Fill in the surname, given name, sex, date of birth, phone, district and next of kin. National ID, village, blood group, known allergies and a photo are optional but useful.
3. Tick the consent box. The patient (or guardian) must agree to the facility keeping their health record under the Data Protection and Privacy Act, 2019.
4. Select **Save patient**. Tunzuri gives the patient a unique patient number.

**Duplicate check.** If the phone number, name and date of birth match an existing record, Tunzuri stops and shows the match. Open the existing record instead of creating a second one. Only if you are sure this is a different person, tick **Register anyway** and save again.

Known allergies show in red on every visit, lab request and prescription for that patient.

## Finding a patient

The **Patients** screen (Patient directory) lets you search by name, phone number or patient number, and sort the list. It also shows totals: all patients, inpatients, records opened today and insured patients. Select a name to open the patient's record, with demographics, next of kin, a clinical brief, active orders and visit history.

To correct details, open the patient and select **Edit**.

## Opening a visit

From the patient's record, use **Open a visit**:

| Field | What to choose |
| --- | --- |
| Visit type | Outpatient or Emergency |
| Priority | Routine, Urgent or Emergency |
| Payer | Cash, Insurance or Corporate |
| Department | Where the patient is going |
| Insurance scheme and member number | Only for insurance or corporate visits |
| Chief complaint | The reason for the visit, in the patient's words |

Select **Open visit**. The patient joins the triage queue.

## Appointments

Go to **Appointments** to book and check in planned visits.

**Booking for a registered patient**

1. Choose **Already registered**.
2. Type the patient's name, phone or patient number and choose them from the matches.
3. Choose the department, the date and time (**When**) and a reason, then select **Book appointment**.

**Booking for a new patient**

Choose **New patient**. You can register the patient and book the visit in one step. Fill in the same details as for registration, tick consent, then choose the department and time.

**On the day**

- When the patient arrives, open **Check in**, choose who pays, and select **Open visit**. The patient joins the triage queue.
- If they do not come, select **No-show**. If the appointment is called off, select **Cancel**.

# Queues

**Queues** shows every service point (Triage, Consultation, Laboratory, Radiology, Pharmacy, Cashier) and who is waiting.

1. Select **Call next to …** for your service point. Tunzuri picks the next patient by priority and arrival time.
2. Serve the patient.
3. Select **Done** when they leave your service point.

You can only call patients for service points you have access to.

# Triage

Open the visit (from the queue or the patient's record). Under **Triage**, open **Record vital signs**:

1. Enter the vital signs, the chief complaint and any notes.
2. Set the **Priority**. Emergencies go to the front of every queue that follows.
3. Select **Save triage**. The patient moves to the consultation queue.

# Consultation

Open the visit and work through the sections in order.

## Notes and diagnosis

Open **Write consultation**, write your history, examination and plan, then search for and tick one or more ICD-10 diagnoses. Select **Save consultation**. Diagnoses feed the HMIS 105 report automatically.

## Ordering lab tests

Under **Results → Order lab tests**, type to filter the test list, tick the tests and add clinical notes. Select **Order tests**. The patient joins the laboratory queue and each test is charged to the bill. You can **Cancel** a test that has not been resulted.

## Ordering imaging

Under **Order imaging**, search for the study, add clinical notes, and select **Order imaging**.

## Prescribing

Under **Prescriptions → Prescribe**:

1. Search for the medicine. The list shows its unit and price.
2. Enter the dose, frequency, number of days and quantity.
3. Select **+ Add another medicine** for more lines, or **Remove this medicine** to drop one.
4. Select **Prescribe**.

Prescribing does not move stock or charge the patient. The pharmacist does both when they dispense.

## Admitting a patient

Under **Admit**, search for a free bed by ward or bed name, enter the working diagnosis, and select **Admit**. If no bed is free, beds being cleaned can be marked clean on the **Wards** page.

## Completing the visit

The **Bill** section shows every charge. Select **Complete visit** when the patient is finished. Tunzuri only allows this once the cash balance is settled and no prescriptions or lab tests are still pending.

# Laboratory

**Laboratory** lists every test waiting, emergencies first, then urgent, then oldest. The top of the screen shows tests waiting, urgent requests, the longest wait and tests verified today. Use the search box to find a patient or test.

For each test:

1. Check the patient, age, visit and any allergies shown.
2. Enter the result. The reference range is shown beside the box.
3. Select **Verify**.

Verifying releases the result to the clinician on the visit and sends the patient a text message. Reagents used by each test are taken from the laboratory store automatically, if the administrator linked a reagent to the test in the price list.

# Radiology

**Radiology** lists imaging orders. Open an order, write the report, attach the image if you have one, and select **Save report**. The report appears on the visit.

# Pharmacy

The Pharmacy screen is the prescription queue. Everything else opens from the buttons at the top: **Shelf**, **Request stock**, **Return stock**, **Add stock** and **Stock levels**.

## Dispensing a prescription

Each row shows the medicine with a label for its form (*Tablet*, *Syrup*, *Injection*, *Topical*…), the dose, frequency and duration, the patient and visit, how much is still to dispense, and how much is on the pharmacy shelf. The shelf quantity turns red when there is not enough to fill the prescription.

1. Select **Dispense** on the row. A form opens with the full prescription and the patient's allergies, if any.
2. Check the quantity to dispense. It is counted in the medicine's dispensing unit (tablets, bottles, tubes, ml) and starts at the amount still owed on the prescription. You can dispense part now and the rest later.
3. Read the green instruction under the form. It tells you exactly what to hand over, and changes as you change the quantity (see the table below).
4. If you must give a different product, search for it under **Substitute medicine**. Leave it as *Dispense [medicine]* to give what was prescribed.
5. Select **Dispense**.

| Kind of medicine | Example instruction |
| --- | --- |
| Tablets and capsules | *Count out 21 tablets from the tin* |
| Syrups, creams, drops, IV fluids and other whole items | *Hand over 1 bottle (100 ml)* |
| Mixtures kept in bulk | *Measure out 200 ml from the can* |

Dispensing takes stock from the pharmacy shelf, using the batch that expires first, and charges the patient at the price for their payer. A cash patient collects the medicine once the bill is paid at the cashier; until then the row shows *Waiting for collection*. Insured patients can collect straight away.

To drop a line that will not be dispensed, open **Dispense** and select **Cancel this prescription line**. A line that has already been dispensed cannot be cancelled.

If something is wrong, for example more than the shelf holds, the form reopens with your entries and the message.

## Watching the pharmacy shelf

- When medicines are at or below their reorder level, an amber bar at the top names them. Select **Review and restock** to open the request form.
- **Shelf** opens every batch on hand with its expiry date and quantity. Type in the filter box to find a medicine. Expiry dates within 90 days are shown in red. Select a medicine's name to open its stock card.
- **Stock levels** opens the full stock picture for every store.

## Restocking the pharmacy

There are two ways to get stock onto the pharmacy shelf.

**From the main store (requisition).** Use this when the stock is already in the facility's main store.

1. Select **Request stock**. The form lists the low items and is already filled with a suggested quantity for each: enough to bring it back to twice its reorder level, rounded up to whole packs (for example *1 tin* rather than 850 tablets). Each line can be counted in packs or in units.
2. Change the quantities, remove lines, or select **Add another item**.
3. Select **Send requisition**. The main store sees it on the Inventory screen and issues the stock.

**Straight onto the shelf (add stock).** Use this when a supplier or donor delivers directly to the pharmacy, or to record stock already on the shelf.

1. Select **Add stock**.
2. Search for the item and choose where it came from: *Delivery without a purchase order*, *Donation* or *Opening balance*.
3. Enter the quantity, and choose whether you are counting packs (for example cartons) or units (bottles). Add the batch number, expiry date and cost per pack or unit if you have them. Put the supplier or delivery note number in **Notes**.
4. Select **Add stock**. The stock is on the pharmacy shelf immediately, and the stock card records who added it and why.

To send stock back, for example because it is overstocked or close to expiry, select **Return stock**. Enter the item, quantity and a reason. The batch and expiry date go back with the stock.

You need the Stores area (or the Pharmacy and stores role) to see **Request stock**, **Return stock** and **Add stock**.

# Cashier

## Shifts

You must open a shift before taking money.

1. Enter the **Opening float** (cash in the drawer at the start) and select **Open shift**.
2. At the end of your shift, count the cash, enter it under **Cash counted in the drawer**, and select **Close shift**. Tunzuri records what you should hold (the float plus cash taken) and any difference.

## Taking a payment

**Bills waiting for payment** lists every cash bill with a balance.

1. Choose the **Method**: Cash, MTN Mobile Money, Airtel Money or Card.
2. Check the **Amount**. It starts at the full balance; enter less for a part payment.
3. For mobile money or card, enter the transaction **reference**.
4. Select **Take payment**. Print the receipt from the receipt page.

## Waivers

If a patient cannot pay, select **Patient cannot pay? Request a waiver**, enter the amount and reason, and select **Send for approval**. The administrator approves or rejects it under **Waivers**. The cashier cannot approve their own request.

# Insurance and corporate accounts

## Schemes

Add each insurer or corporate client under **Schemes**: name, kind, the cover limit per visit, and whether charges need an approved preauthorisation first. Patients on a scheme are registered with that scheme and their member number when their visit is opened.

## Preauthorisations

Under **Request a preauthorisation**, search for the insured visit by patient name or visit number, describe the service, and enter the amount. Select **Request**. When the insurer replies, enter their reference and select **Approve** or **Reject**.

## Claims

1. Under **Claims**, choose the scheme and the date range, then select **Compile claim**. Tunzuri gathers every charge for that scheme in the period.
2. Check the claim, then select **Submit to insurer**.
3. When payment arrives, enter the **Amount received** and **Remittance reference**, and select **Record remittance**.

# Wards

## The ward board

**Wards** shows every ward, its beds and the inpatients on it. A bed that was just vacated shows as *cleaning*; select **Mark clean** when it is ready for the next patient.

## Caring for an inpatient

Select a patient to open their admission.

- **Nursing notes**: write a note and select **Save note**.
- **Medication chart**: search for the medicine, enter the dose and status, and select **Chart dose**. Charting does not move stock. The stock already left when the pharmacy dispensed it.
- **Ward stock used**: when you use supplies from the ward store, such as a cannula or a giving set, choose the store, search for the item, enter the quantity and select **Issue**. Billable items are charged to the patient. Non-billable items, such as gloves and cotton wool, are recorded as a cost to the ward.

## Discharge

Under **Discharge**, write the discharge summary, choose the outcome, and select **Discharge**. As with completing a visit, the cash balance must be settled and nothing may be pending. After discharge the record is closed and the bed goes to *cleaning*.

# Maternity

- **Antenatal visit**: enter the mother's patient number (you can type her name and pick from the suggestions), gestation in weeks, fundal height, fetal heart rate, blood pressure and notes, then select **Save antenatal visit**.
- **Delivery register**: enter the mother's patient number, the delivery time, mode, outcome, baby's sex, birth weight and Apgar score, then select **Save delivery**.

Recent antenatal visits and deliveries are listed below the forms.

# Theatre

1. Under **Book theatre**, enter the patient number, procedure and time, and select **Book**.
2. After the operation, find the booking in the list, write the findings and operation note, and select **Save operation note**.

# Inventory and stock

Tunzuri keeps one stock ledger for the whole facility. Every unit that comes in or goes out is recorded with the item, batch, store, quantity, reason, the patient or department, and the person who did it. Stock on hand in any store is always the total of its movements, so nothing changes without a record.

## How stock moves

Each facility starts with these stores: **Main store**, **Pharmacy**, **Laboratory**, **Female ward store**, **Male ward store** and **Theatre store**. New wards can have their own store.

1. Stock arrives in the **main store** from a supplier delivery, a donation, or as an opening balance. A department store, such as the pharmacy, can also receive a delivery directly with **Add stock**.
2. Department stores **request** stock from the main store (a requisition), and the storekeeper **issues** it.
3. Departments **use** stock: the pharmacy dispenses to patients, wards issue to inpatients, and the laboratory uses reagents for tests.
4. Unwanted stock is **returned** to the main store. Losses, damage, expiry and count differences are **adjusted** with the administrator's approval.

## Dosage forms, units and packs

Medicines come in many forms, and what you buy is rarely what you hand out. A pharmacy buys paracetamol in a tin of 1,000 tablets but dispenses a few tablets at a time. It buys amoxicillin suspension in cartons of 50 bottles but sells one whole bottle. Tunzuri keeps these details for every item:

| What | Meaning | Examples |
| --- | --- | --- |
| Dosage form | What kind of product it is, which decides how it is handed out | Tablet, capsule, syrup or suspension, mixture measured out, injection, IV fluid, cream or ointment, drops, inhaler, powder, suppository, medical supply, lab reagent |
| Dispensing unit | The smallest amount you hand out. All stock, prices, prescriptions and reorder levels are counted in it | tablet, capsule, bottle, tube, vial, ampoule, sachet, ml, pair, piece, test |
| Each one contains | Optional. What one dispensing unit holds | 100 ml (a syrup bottle), 20 g (a cream tube), 500 ml (an IV bottle), 400 g (a tin of powder) |
| Purchase pack | Optional. How you buy it, and how many dispensing units are in one | tin of 1,000 tablets, carton of 50 bottles, box of 10 vials, can of 5,000 ml |

Common patterns:

| Product | Dispensing unit | Purchase pack | At the counter |
| --- | --- | --- | --- |
| Tablets or capsules from a tin | tablet | tin of 1,000 | Count out tablets |
| Syrup or suspension | bottle (100 ml) | carton of 50 | Hand over whole bottles |
| Cream, ointment, eye ointment | tube (15 g) | box of 12 | Hand over whole tubes |
| Injection | vial or ampoule | box of 10 | Hand over vials |
| IV fluid | bottle (500 ml) | carton of 20 | Hand over bottles |
| Mixture kept in a large container | ml | can of 5,000 ml | Measure out ml |
| Powder in a tin | tin (400 g) | carton of 24 | Hand over whole tins |
| Gloves, syringes, cannulas | pair or piece | box of 50 or 100 | Hand over pieces |

Choosing a dosage form fills in the usual unit and pack for it. Always change the pack to match what your supplier actually delivers. Pack sizes differ between suppliers and over time.

Wherever you enter stock (purchase orders, receiving, requisitions, adding stock, returns) you can count in packs or in units, and Tunzuri shows the conversion as you type, for example *= 2,000 tablets*. The stock is always stored in dispensing units, and lists show both, for example *2,150 tablets (2 tins + 150)*.

**Changing an item.** Open the item's stock card and select **Edit item** to change its name, form, contents, pack, price, reorder level, or to make it inactive. Once an item has stock, its dispensing unit cannot change, because every balance and movement is counted in it. If you start dispensing it differently, for example by the strip instead of by the tablet, add a new item.

## Setting up your items

A new facility has no stock items. There are three ways to add them, all under **Inventory → Items and stock**.

**Starter list (fastest).** Open the **Starter list** tab and select **Load the starter list**. Tunzuri adds about 40 medicines, supplies and lab reagents that most facilities stock, including tablets in tins, syrups and suspensions by the bottle, creams and eye ointment by the tube, IV fluids, and a mixture measured in ml. Each comes with a typical pack, a suggested price and a reorder level. The packs are common examples, not official figures, so check them against your supplier. Items you already have are skipped, so it is safe to run again. Check the prices afterwards under **Price list**.

**One item at a time.** Open the **New item** tab:

| Field | Meaning |
| --- | --- |
| Name and strength | For example *Amoxicillin 125mg/5ml suspension* |
| Dosage form | Fills in the usual unit and pack (see *Dosage forms, units and packs*) |
| Dispensed and counted in | The dispensing unit: tablet, bottle, tube, ml, pair |
| Each one contains | Optional, for example 100 ml |
| Bought in / How many in each | The purchase pack, for example *tin* and *1000*. Leave blank if you buy by the unit |
| Price to patient per unit | The price a cash patient pays per dispensing unit |
| Reorder when stock falls to | In dispensing units, the level at which the item shows as low |
| Category | Medicine, Medical supply or Lab reagent |
| Billing | Billable medicine, billable consumable, or non-billable (a department cost). Set automatically from the category; change it if needed |
| Code (optional) | Leave blank and Tunzuri makes one from the name |

If you already have some on the shelf, fill in **Opening stock** on the same form: quantity (in packs or units), cost, store, batch number and expiry date.

**Import from a spreadsheet (best for a long list).** Open the **Import from CSV** tab.

1. Select **Download the template**.
2. Fill it in with Excel or Google Sheets, one row per item, then save it as **CSV**.
3. Choose the file and select **Import**.

| Column | Required | Notes |
| --- | --- | --- |
| name | Yes | Name and strength |
| form | One of form or unit | tablet, capsule, oral_liquid, bulk_liquid, injection, infusion, topical, drops, inhaler, powder, suppository, consumable, reagent, other |
| unit | One of form or unit | The dispensing unit: tablet, bottle, ml, pair… |
| unit_content | No | What one unit holds, for example 100 ml |
| pack_unit | No | How you buy it, for example tin, carton, box |
| pack_size | No | Dispensing units in one pack, for example 1000 |
| category | No | medicine, consumable or reagent (default medicine) |
| billing_type | No | billable_medicine, billable_consumable or non_billable |
| cash_price | No | Price per unit in UGX |
| reorder_level | No | Low-stock level |
| sku | No | Your own code; made from the name if blank |
| store | No | Store name exactly as in Tunzuri; default Main store |
| quantity | No | Opening stock, counted in dispensing units (tablets, not tins). Leave blank to add the item only |
| batch_number | No | Made up automatically if blank |
| expiry_date | No | Written as YYYY-MM-DD, for example 2027-06-30 |
| unit_cost | No | What you paid per dispensing unit, in UGX |

An item that already exists, matched by name or code, is not created again; only its stock is added. So you can also use an import for a large stock top-up. If any row has a mistake, nothing is saved and Tunzuri lists the rows to fix, for example *Row 3: The unit field is required.* Fix the file and upload it again.

## Adding stock without a purchase order

Open the **Add stock** tab when stock arrives outside the purchase-order process, or when you are entering what is already on your shelves.

1. Search for the item and choose the store.
2. Under **Where it came from**, choose *Opening balance*, *Delivery without a purchase order* or *Donation*.
3. Enter the quantity in packs or units, and the cost per pack or unit. Add the batch number and expiry date if you have them.
4. Use **Notes** for the supplier, donor or delivery note number.
5. Select **Add stock**.

## Buying from suppliers

**Placing a purchase order**

1. Under **Purchase orders**, search for the supplier, or select **Add a new supplier** and type the name.
2. Search for the item, enter the **number of packs** (tins, cartons, boxes) and the **cost per pack**, and select **Send order**. Tunzuri shows how many dispensing units that is, and keeps the pack size on the order so it still reads correctly if the item's pack changes later.

**Receiving goods**

When the delivery arrives, find the order and, for each line, enter the batch number, expiry date and quantity received, in packs or units. Select **Receive**. The stock goes into the main store. If only part of an order arrives, receive what came. The order stays open until everything is received.

## Requisitions: moving stock to a department

Any department store can ask the main store for stock.

1. Under **Requisitions from the main store**, choose the store the stock is for.
2. Select **Fill from reorder levels** to add every item that store is low on, with suggested quantities rounded up to whole packs. Or search for items yourself, using **Add another item** for each one. Each line can be counted in packs or units.
3. Select **Send requisition**.

The storekeeper sees open requisitions at the top of the list, with *issued X of Y* for each line, and selects **Issue**.

- If the main store has enough, everything moves and the requisition shows *Issued*.
- If the main store is short, Tunzuri issues what is there and marks the requisition *Partial*. When new stock arrives, select **Issue the rest**.
- If the main store has none of the items, nothing moves and Tunzuri says so.

Moving stock between stores never charges a patient.

## Returning stock

Use **Return stock to the main store** for stock a department no longer needs. Choose the store, item and quantity, and give a reason, for example *overstocked* or *near expiry*. Batches and expiry dates go back with it.

## Adjustments: expiry, damage, loss and counts

When stock on the shelf does not match Tunzuri:

1. Under **Stock adjustments**, choose the store and item.
2. Enter the **Change in quantity** in dispensing units: a minus number to remove (for example −5 tablets), a plus number to add.
3. Choose the reason (*Expired*, *Damaged*, *Lost*, *Count did not match*) and add notes.
4. Select **Request adjustment**.

Stock does not change until the administrator selects **Approve**. The approval is recorded on the stock card.

## Stock levels

**Stock levels** shows every item and how many are in each store, the total, its reorder level, how many were used in the last 30 days, and the nearest expiry date. Each item has a status:

| Status | Meaning |
| --- | --- |
| In stock | Nothing needs attention |
| Low | The total is at or below the reorder level. Time to order |
| Expiring soon | A batch expires within 90 days. Use it first or return it |
| Has expired stock | A batch is past its expiry date. Remove it with an adjustment |
| Out of stock | None in any store |

Search for an item by name, or tick **Only items that need attention** to see just the items to act on.

## The stock card: an item's full history

Select any item to open its stock card.

- **On hand now** lists every batch in every store, with its expiry date and unit cost.
- **Stock card** lists every movement in and out, newest first. Each row shows when it happened, the store, what happened, the quantity in or out, the balance left afterwards, the batch, the department, the patient (for dispenses and ward issues), who did it (and who approved it, for adjustments), and the document it belongs to (requisition, goods receipt or prescription).

Filter by store and dates to answer questions like "where did the 200 tablets issued to the pharmacy last week go?"

## Stock usage: what each department and employee used

**Stock usage** totals what was used up in a period: dispensed, issued on the wards, used by a department, or used by the laboratory. Transfers between stores are not counted as use.

1. Choose the dates (**From**, **To**).
2. Under **Group by**, choose **Department**, **Employee** or **Item**.
3. Narrow it if you need to by kind of use, department, employee or item. Choose *Adjustment* to see write-offs, or *Returned to main store* to see returns.
4. Select **Update**.

Each group shows the quantity of each item and its value at cost, with the total value at the top. Select **Export CSV** to open the same figures in Excel.

# Reports

**Reports** builds the facility's figures from daily work, for the dates you choose:

- **Cash collected** in the period.
- **Dispenses without a charge**: should always be zero. Anything else needs checking.
- **Ward issues in 7 days.**
- **HMIS 105 outpatient attendance**: new and repeat attendances by sex and age band, plus diagnoses by ICD-10 code.
- **HMIS 108 inpatient**: admissions, discharges, deaths, bed days and bed occupancy.

Select **Export HMIS CSV** to download the figures for the Ministry of Health return.

# Administration

These screens are for the facility administrator.

## Users

**Users** lists everyone who can sign in. Search by name or email.

**Adding a user**

1. Select **Add user**. Enter the name, email, phone and a temporary password.
2. Choose **Administrator, full access**, or **Chosen areas**.
3. For chosen areas, pick the person's **Department**. Tunzuri ticks the areas that department normally uses. Add or remove ticks as needed.
4. Select **Add user**. Give the person their email and temporary password, and ask them to change it when they first sign in.

To change someone's access, or to stop them signing in when they leave, open their account, change the details or set them inactive, and select **Save changes**. Deactivate accounts rather than sharing them. Every action is recorded against a person.

## Departments

Add departments with a name, code and kind. The kind decides which areas are ticked when you put someone in that department.

## Wards and beds

**Wards & beds** lets you:

- Add a ward with a name, short code, department and number of beds. Tick **Also create a ward stock store** so the ward can hold and request its own supplies.
- Grow or shrink a ward, rename beds (for example *Cot 3* or *Side room*), and close beds that are out of service.
- Remove a bed that was never used. A bed with admission history can only be closed.

## Price list

**Price list** holds everything the facility charges for:

- **Services**: consultation, registration, bed day and so on, with a code, name, category and cash price.
- **Stock items**: medicines and supplies (see *Setting up your items*).
- **Lab tests**: priced through a linked service. Add the service first. You can also set the specimen, result unit, reference range, and the reagent used by each test, so reagent stock goes down automatically.
- **Imaging studies**: priced through a linked service.

## Audit trail

**Audit trail** lists every recorded action: who did what, and when. Use it to answer questions about a change to a record, price or user.

# Setting up a new facility

When a facility signs up, its owner confirms their email and goes through a short setup wizard:

1. **Facility details**: name, type, district, address, phone, email and MoH facility code. These appear on receipts, reports and patient documents.
2. **Wards & beds**: two wards are created to start. Rename them, change the number of beds, or add more.
3. **Ready to go live**: a summary of what the facility starts with. Select **Go live**.

Every new facility starts with standard departments, the six stock stores, two wards and starter prices for registration, consultation and bed days.

**Suggested first-week checklist for the administrator**

1. Add your staff under **Users** and give each person their areas.
2. Check **Departments** and **Wards & beds**.
3. Set your prices under **Price list**: services, lab tests and imaging studies.
4. Add stock items: load the **Starter list** or import your own list (*Inventory → Items and stock*).
5. Count what is on your shelves and enter it as **opening balances**, in the store where it actually is.
6. Set realistic **reorder levels** so Stock levels and the pharmacy warn you in time.
7. Add your insurance schemes and corporate clients.

# Platform administrator

The platform administrator runs Tunzuri for all facilities. They never see patient records.

- **Platform overview**: facility counts, new sign-ups, visits, bed occupancy and facilities that need attention.
- **Facilities**: search and sort facilities; **Provision facility** to create one with its first administrator and a temporary password (it goes live straight away); **Deactivate** or **Activate** a facility.
- **Administrators** (per facility): add another administrator or switch one off.

# Troubleshooting and common questions

**I cannot see a screen I need.** Your account does not have that area. Ask your administrator.

**I was signed out.** Tunzuri signs you out after 15 minutes without activity. Sign in again.

**The pharmacy says there is not enough stock, but the main store has plenty.** Dispensing only uses stock on the pharmacy shelf. Select **Request stock** on the Pharmacy screen and ask the storekeeper to issue it.

**A requisition shows "Partial".** The main store did not have enough. Once stock arrives, the storekeeper selects **Issue the rest**.

**The stock in Tunzuri does not match the shelf.** Count again. If it still differs, request an adjustment with the reason *Count did not match*. Use the item's stock card to trace where the difference started.

**I cannot complete a visit or discharge a patient.** The cash balance is not settled, or a prescription or lab test is still pending. Take payment, dispense or cancel the prescription, and release or cancel the lab test.

**The CSV import was rejected.** Nothing was saved. Fix the rows listed in the message and upload the whole file again. Common causes: a missing name or unit, a store name spelled differently from Tunzuri, or an expiry date not written as YYYY-MM-DD.

**There are no items to choose from.** No stock items have been added yet. Someone with the Stores area, or the administrator, adds them under *Inventory → Items and stock*.

**A patient was registered twice.** Ask your administrator. Do not keep using both records.

# Glossary

| Term | Meaning |
| --- | --- |
| Batch | One lot of stock with its own batch number, expiry date and cost |
| Dispensing unit | The smallest amount handed out (tablet, bottle, ml). All stock is counted in it |
| Dosage form | The kind of product (tablet, syrup, injection…), which decides how it is handed out |
| Purchase pack | How an item is bought (tin, carton, box) and how many dispensing units it holds |
| Billable / non-billable | Whether using an item charges the patient or is a department cost |
| Opening balance | Stock already on the shelf when you start using Tunzuri |
| Payer | Who pays for a visit: cash, insurance or corporate |
| Preauthorisation | The insurer's approval before a service is given |
| Reorder level | The quantity at which an item shows as low |
| Requisition | A department's request for stock from the main store |
| Stock card | An item's full history of movements, with the balance after each one |
| Stock ledger | The single record of every stock movement in the facility |
| Waiver | Part or all of a bill forgiven, with the administrator's approval |
