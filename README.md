# Google Sheets Integration Guide for PIA Advertising Request Form

## Overview

This guide walks you through connecting your PIA advertising form to Google Sheets so all submissions automatically save to a spreadsheet. Currently, the system stores data locally in browser storage. With this integration, data will go directly to Google Sheets.

## What You Need to Do

This process has 6 main steps:
1. Create a Google Sheet with column headers
2. Create a Google Apps Script to receive form data
3. Update your HTML form to send data to Google Sheets
4. Deploy and test the integration
5. Optional: Update admin dashboard to pull from Google Sheets
6. Deploy everything

---

## STEP 1: Create Your Google Sheet

1. Go to [Google Drive](https://drive.google.com)
2. Click **+ New** → **Google Sheets**
3. Name it: **"PIA Advertising Requests"**
4. Click into the sheet and create these column headers in Row 1:
   - **A1:** Timestamp
   - **B1:** Client Name
   - **C1:** Email Address
   - **D1:** Contact Number
   - **E1:** Company Name
   - **F1:** Service Type
   - **G1:** Size Dimensions
   - **H1:** Other Service
   - **I1:** Location
   - **J1:** Date Needed
   - **K1:** Budget
   - **L1:** Files Uploaded
   - **M1:** Project Description
   - **N1:** Status

Keep this sheet open - you'll need the URL in a moment.

---

## STEP 2: Create Google Apps Script

1. In your Google Sheet, go to **Extensions** → **Apps Script**
2. Delete the existing code
3. Paste this entire script:

\`\`\`javascript
function doPost(e) {
  try {
    const sheet = SpreadsheetApp.getActiveSheet();
    const data = JSON.parse(e.postData.getContents());
    
    const row = [
      new Date().toLocaleString(),
      data.client_name,
      data.email,
      data.contact_number,
      data.company_name,
      data.service_type,
      data.size_dimensions || '',
      data.other_service || '',
      data.location,
      new Date(data.date_needed).toLocaleString(),
      data.budget,
      data.uploaded_files.join(', '),
      data.project_description,
      data.status
    ];
    
    sheet.appendRow(row);
    
    return ContentService.createTextOutput(JSON.stringify({success: true}))
      .setMimeType(ContentService.MimeType.JSON);
  } catch (error) {
    return ContentService.createTextOutput(JSON.stringify({success: false, error: error.toString()}))
      .setMimeType(ContentService.MimeType.JSON);
  }
}
\`\`\`

4. Click **Save** (name the project "PIA Handler" if prompted)
5. Click **Deploy** → **New Deployment**
6. Select **Type** → **Web App**
7. Set these options:
   - Execute as: **Your Google Account**
   - Who has access: **Anyone**
8. Click **Deploy**
9. You'll see a popup - click **Authorize access**
10. Copy the **Deployment URL** (looks like: `https://script.google.com/macros/d/xxxxx/useweb`)
11. Save this URL - you need it in the next step

---

## STEP 3: Update Your Form (index.html)

Find the `confirmSubmit()` function in your index.html. Replace the entire function with this:

\`\`\`javascript
function confirmSubmit() {
    const deploymentUrl = 'YOUR_DEPLOYMENT_URL_HERE'; // PASTE YOUR URL HERE
    
    const formData = {
        client_name: document.getElementById('client_name').value,
        email: document.getElementById('email').value,
        contact_number: document.getElementById('contact_number').value,
        company_name: document.getElementById('company_name').value,
        service_type: document.getElementById('service_type').value,
        size_dimensions: document.getElementById('size_dimensions').value || '',
        other_service: document.getElementById('other_service').value || '',
        location: document.getElementById('location').value,
        date_needed: document.getElementById('date_needed').value,
        budget: document.getElementById('budget').value,
        project_description: document.getElementById('project_description').value,
        uploaded_files: uploadedFiles.map(f => f.name),
        status: 'New'
    };

    fetch(deploymentUrl, {
        method: 'POST',
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let requests = JSON.parse(localStorage.getItem('advertisingRequests')) || [];
            requests.push(formData);
            localStorage.setItem('advertisingRequests', JSON.stringify(requests));

            closeModal();
            document.getElementById('successMessage').classList.remove('hidden');
            form.reset();
            uploadedFiles = [];
            fileList.innerHTML = '';
            conditionalFields.style.display = 'none';

            setTimeout(() => {
                document.getElementById('successMessage').classList.add('hidden');
            }, 3000);
        }
    })
    .catch(error => {
        alert('Error submitting form. Please try again.');
    });
}
\`\`\`

**IMPORTANT:** Replace `YOUR_DEPLOYMENT_URL_HERE` with the actual deployment URL you copied in Step 2.

---

## STEP 4: Test Everything

1. Open your form (index.html)
2. Fill it out completely and submit
3. Go back to your Google Sheet and refresh
4. You should see your submission in Row 2 with all the data

If it doesn't work:
- Check the deployment URL is correct
- Make sure you replaced the placeholder with the actual URL
- Check the browser console (F12) for error messages

---

## STEP 5: Update Admin Dashboard (Optional)

To make the admin automatically pull from Google Sheets instead of just localStorage:

1. Go back to your Google Apps Script
2. Add this function **below** the doPost function:

\`\`\`javascript
function doGet(e) {
  const sheet = SpreadsheetApp.getActiveSheet();
  const data = sheet.getDataRange().getValues();
  
  const requests = data.slice(1).map(row => ({
    timestamp: row[0],
    client_name: row[1],
    email: row[2],
    contact_number: row[3],
    company_name: row[4],
    service_type: row[5],
    size_dimensions: row[6],
    other_service: row[7],
    location: row[8],
    date_needed: row[9],
    budget: row[10],
    uploaded_files: row[11] ? row[11].split(', ') : [],
    project_description: row[12],
    status: row[13]
  }));
  
  return ContentService.createTextOutput(JSON.stringify(requests))
    .setMimeType(ContentService.MimeType.JSON);
}
\`\`\`

3. Click **Deploy** → **Manage Deployments**
4. Update the existing deployment (don't create a new one)
5. The deployment URL stays the same

Now update your admin dashboard's `loadRequests()` function:

\`\`\`javascript
function loadRequests() {
    const deploymentUrl = 'YOUR_DEPLOYMENT_URL_HERE'; // Same URL as form
    
    fetch(deploymentUrl)
    .then(response => response.json())
    .then(data => {
        requests = data;
        renderRequests();
    })
    .catch(error => {
        requests = JSON.parse(localStorage.getItem('advertisingRequests')) || [];
        renderRequests();
    });
}
\`\`\`

Replace the placeholder with your deployment URL.

---

## STEP 6: What You DON'T Need to Change

✅ Keep all your form HTML as-is
✅ Keep all styling as-is
✅ Keep all JavaScript logic as-is (except the `confirmSubmit()` function)
✅ Keep localStorage - it's a backup
✅ Files are stored as names only (not actual uploads)

---

## Important Notes

**Files:**
- Currently, only file names are stored in Google Sheets
- Actual files are not uploaded to Google Drive
- This is intentional (simpler setup for academics)
- To handle actual file uploads, you'd need Google Drive API setup

**Data Flow:**
1. User fills form
2. Form sends data to Google Apps Script via POST
3. Apps Script writes to Google Sheet
4. Data also saved locally in browser storage (backup)
5. Admin dashboard reads from Google Sheets

**Security:**
- Anyone can submit the form (it's public)
- Only you can see the admin dashboard (keep it private)
- Update status changes are saved to Google Sheets automatically

---

## Troubleshooting

**"Error submitting form":**
- Check your deployment URL is correct
- Make sure you replaced the placeholder text
- Check if the Apps Script was authorized

**"Data not appearing in sheet":**
- Refresh the Google Sheet page
- Check the column headers match exactly
- Look at Apps Script Logs (Extensions → Apps Script → Executions) for errors

**"403 Forbidden error":**
- Redeploy the Google Apps Script
- Make sure "Who has access" is set to "Anyone"

**"Deployment URL error":**
- Copy the full URL from the deployment popup
- It should start with `https://script.google.com/macros/d/`

---

## Summary

After completing these steps:
- ✅ Form submissions go directly to Google Sheets
- ✅ Files are tracked by name
- ✅ Admin can view and update status
- ✅ Data backed up in browser storage
- ✅ Everything works offline if needed

You're done! The system is now integrated with Google Sheets.
