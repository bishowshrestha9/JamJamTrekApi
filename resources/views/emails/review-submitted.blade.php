<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Review Submitted</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 0 0 5px 5px;
        }
        .review-info {
            background-color: white;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #4CAF50;
        }
        .rating {
            color: #f39c12;
            font-size: 20px;
        }
        .label {
            font-weight: bold;
            color: #555;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #777;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🌟 New Review Submitted</h1>
    </div>
    
    <div class="content">
        <p>Hello Admin,</p>
        <p>A new review has been submitted on your website. Here are the details:</p>
        
        <div class="review-info">
            <p><span class="label">Name:</span> {{ $review->name }}</p>
            <p><span class="label">Email:</span> {{ $review->email }}</p>
            <p><span class="label">Rating:</span> <span class="rating">{{ str_repeat('⭐', intval($review->rating)) }}</span> ({{ $review->rating }}/5)</p>
            
            @if($review->trek)
            <p><span class="label">Trek:</span> {{ $review->trek->title }}</p>
            @endif
            
            <p><span class="label">Review:</span></p>
            <p style="background-color: #f5f5f5; padding: 10px; border-radius: 3px;">
                {{ $review->review }}
            </p>
            
            <p><span class="label">Status:</span> 
                <span style="color: {{ $review->status ? '#4CAF50' : '#f39c12' }}">
                    {{ $review->status ? 'Approved' : 'Pending Approval' }}
                </span>
            </p>
            
            <p><span class="label">Submitted at:</span> {{ $review->created_at->format('F j, Y, g:i a') }}</p>
        </div>
        
        <p>Please review and take appropriate action.</p>
    </div>
    
    <div class="footer">
        <p>This is an automated notification from {{ config('app.name') }}</p>
        <p>{{ now()->year }} &copy; All rights reserved</p>
    </div>
</body>
</html>
