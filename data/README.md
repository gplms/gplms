# Dummy Data and Correct CSV Format for System Uploads.

This folder contains **dummy data** and guidelines to ensure that every CSV file you upload to the system is formatted correctly. Adhering to this format is crucial to prevent errors during the import process.

## Correct CSV Format

Below is the required structure for the CSV files:

| **Header**               | **Description**                                           |
|---------------------------|-----------------------------------------------------------|
| **Title**                | The title of the item (e.g., book, magazine).             |
| **Type Name**            | The type of item (e.g., Book, Magazine, Journal).         |
| **Category Name**        | The category this item belongs to (e.g., Science, History).|
| **Publisher Name**       | The name of the publisher.                                |
| **Language**             | The language of the item (e.g., EN for English).          |
| **Year**                 | The publication year (e.g., 2022).                        |
| **Edition**              | Edition number if applicable (e.g., 1, 2, 3).            |
| **ISBN**                 | The unique ISBN for books. Leave blank for non-books.     |
| **ISSN**                 | The unique ISSN for journals/magazines. Leave blank for books. |
| **Description**          | A brief description of the item.                         |
| **Authors (semicolon separated)** | List of authors, separated by semicolons.         |

---

> see the <a href = "test-data.csv">test-data.csv</a> for more realistic details.
