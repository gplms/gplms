<?php
// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=library_template.csv');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// CSV header row
fputcsv($output, [
    'Title',
    'Type Name',
    'Category Name',
    'Publisher Name',
    'Language',
    'Publication Year',
    'Edition',
    'ISBN',
    'ISSN',
    'Description',
    'Authors'
]);

// Sample data rows
fputcsv($output, [
    'Introduction to Programming',
    'Book',
    'Computer Science',
    'Tech Publishers',
    'EN',
    '2022',
    '3',
    '1234567890123',
    '',
    'Basic programming concepts',
    'John Smith;Jane Doe'
]);

fputcsv($output, [
    'Science Monthly',
    'Magazine',
    'Science',
    'Science Press',
    'EN',
    '2023',
    '',
    '',
    '9876-543',
    'Monthly science magazine',
    'Editorial Team'
]);

fclose($output);
exit;
?>