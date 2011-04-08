function shopform()
{
	this.version = '0.1';
	this.prices = new Array();
	this.taxes = new Array();
	this.count = new Array();
	this.products = new Array();
	this.sum = 0;

	shopform.prototype.add_product = function(product_id, price, tax, count)
	{
		this.prices[product_id] = price;
		this.taxes[product_id] = tax;
		this.count[product_id] = count;
		this.products.push(product_id);
		document.getElementById("product_text_" + product_id).value = count;
	}

	shopform.prototype.adjust_count_up = function(product_id)
	{
		this.count[product_id]++;
		document.getElementById("product_" + product_id).value = Math.abs(this.count[product_id]);
		document.getElementById("product_text_" + product_id).value = Math.abs(this.count[product_id]);
		this.calculate();
	}

	shopform.prototype.adjust_count_down = function(product_id)
	{
		if (this.count[product_id] > 0)
			this.count[product_id]--;
		document.getElementById("product_" + product_id).value = Math.abs(this.count[product_id]);
		document.getElementById("product_text_" + product_id).value = Math.abs(this.count[product_id]);
		this.calculate();
	}

	shopform.prototype.set_count = function(product_id, count)
	{
		if (count != NaN && count >= 0)
			this.count[product_id] = count;
		document.getElementById("product_" + product_id).value = Math.abs(this.count[product_id]);
		document.getElementById("product_text_" + product_id).value = Math.abs(this.count[product_id]);
		this.calculate();
	}

	shopform.prototype.calculate = function()
	{
		sum = tax = 0;
		for (i = 0; i < this.products.length; i++)
		{
			product_id = this.products[i];
			sum += this.count[product_id] * this.prices[product_id];
			tax += this.count[product_id] * this.prices[product_id] / 100 * this.taxes[product_id];
		}
		
		sum = Math.round(sum * 100) / 100;
		tax = Math.round(tax * 100) / 100;
		if (sum < 100)
			dispatch = 7;
		else
			dispatch = 0;

		document.getElementById("shopform_sum_pre").innerHTML = '&#8364; ' + sum.toFixed(2);
		document.getElementById("shopform_tax").innerHTML = '&#8364; ' + (tax + dispatch * 0.19).toFixed(2);
		document.getElementById("shopform_dispatch").innerHTML = '&#8364; ' + dispatch.toFixed(2);
		document.getElementById("shopform_sum").innerHTML = '&#8364; ' + (sum + dispatch).toFixed(2);
	}
}

shopform = new shopform();