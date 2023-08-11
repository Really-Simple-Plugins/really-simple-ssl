import * as React from "react";
const SvgHnHonduras = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="HN_-_Honduras_svg__a"
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
    <g mask="url(#HN_-_Honduras_svg__a)">
      <path
        fill="#F7FCFF"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="HN_-_Honduras_svg__b"
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
      <g
        fill="#4564F9"
        fillRule="evenodd"
        clipRule="evenodd"
        mask="url(#HN_-_Honduras_svg__b)"
      >
        <path d="M0 0v4h16V0H0ZM0 8v4h16V8H0ZM8.41 6.566l-.589.309.113-.655-.476-.51h.657l.294-.644.294.643h.657l-.475.511.112.655-.588-.31ZM5.41 5.566l-.589.309.113-.655-.476-.51h.657l.294-.644.294.643h.657l-.475.511.112.655-.588-.31ZM5.41 7.566l-.589.309.113-.655-.476-.51h.657l.294-.644.294.643h.657l-.475.511.112.655-.588-.31ZM11.41 5.566l-.589.309.113-.655-.476-.51h.657l.294-.644.294.643h.657l-.475.511.112.655-.588-.31ZM11.41 7.566l-.589.309.113-.655-.476-.51h.657l.294-.644.294.643h.657l-.475.511.112.655-.588-.31Z" />
      </g>
    </g>
  </svg>
);
export default SvgHnHonduras;
