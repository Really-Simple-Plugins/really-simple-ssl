import * as React from "react";
const SvgVeVenezuela = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="VE_-_Venezuela_svg__a"
      width={16}
      height={12}
      x={0}
      y={0}
      maskUnits="userSpaceOnUse"
      style={{
        maskType: "luminance",
      }}
    >
      <path fill="#fff" d="M0 0h16v12H0z" />
    </mask>
    <g mask="url(#VE_-_Venezuela_svg__a)">
      <path
        fill="#2E42A5"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="VE_-_Venezuela_svg__b"
        width={16}
        height={12}
        x={0}
        y={0}
        maskUnits="userSpaceOnUse"
        style={{
          maskType: "luminance",
        }}
      >
        <path
          fill="#fff"
          fillRule="evenodd"
          d="M0 0v12h16V0H0Z"
          clipRule="evenodd"
        />
      </mask>
      <g mask="url(#VE_-_Venezuela_svg__b)">
        <path
          fill="#FECA00"
          fillRule="evenodd"
          d="M0 0v4h16V0H0Z"
          clipRule="evenodd"
        />
        <path
          fill="#E31D1C"
          fillRule="evenodd"
          d="M0 8v4h16V8H0Z"
          clipRule="evenodd"
        />
        <path
          fill="#F7FCFF"
          d="m4.107 7.62-.911-.41C4.064 5.282 5.695 4.302 8 4.302c2.306 0 3.932.981 4.788 2.91l-.914.406C11.184 6.063 9.924 5.302 8 5.302c-1.924 0-3.192.762-3.893 2.318Z"
        />
      </g>
    </g>
  </svg>
);
export default SvgVeVenezuela;
