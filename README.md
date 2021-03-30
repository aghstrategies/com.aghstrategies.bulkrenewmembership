# com.aghstrategies.bulkrenewmembership

Adds an action "Bulk Renew Memberships" to the Action menu on the Find Memberships Search From.

This action takes you to a screen where you can see details about the Membership and related payment
![Bulk Renew Screen](/images/bulkRenew.png)

## Columns
| Column Name | Description | Editable |
|--|--|--|
| Name | Name of contact | No |
| Membership ID | ID of membership adding a pending payment for | No |
| Contribution Source | Source of the last completed payment for the membership | No |
| Has Pending Payment? | If there is a Pending Payment already for the membership this column shows the ID and source for that pending contribution, this is to help the user decide if they want to create another pending payment. | No |
| Email Address | Email Address of the member | No |
| Financial Type | Financial Type of the new Pending Payment defaults to the same as the last completed payment | yes |
| Total Amount | Total Amount of the new Pending Membership Payment, Defaults to the same as the last completed payment | yes |
| Confirm | New Pending Payments will only be created for rows with this checkbox checked. Defaults to checked if there is no existing pending Payment | yes |
